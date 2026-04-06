<?php

namespace App\Http\Controllers\Api;

use App\Actions\Task\CompleteTaskAction;
use App\Actions\Task\ReorderTaskAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TaskController extends Controller
{
    public function __construct(
        protected CompleteTaskAction $completeTaskAction,
        protected ReorderTaskAction $reorderTaskAction
    ) {
    }

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $query = Task::query()
            ->whereNull('parent_task_id') // Исключаем подзадачи из основного списка
            ->where(function ($q) use ($user) {
                // Inbox tasks
                $q->whereNull('project_id')
                    ->where(function ($sub) use ($user) {
                        $sub->where('created_by', $user->id)
                            ->orWhere('assignee_id', $user->id);
                    });
            })
            // Or tasks in projects user can access
            ->orWhere(function ($q) use ($user) {
                $q->whereNull('parent_task_id') // Снова исключаем подзадачи
                    ->whereHas('project', function ($pq) use ($user) {
                        $pq->where('owner_id', $user->id)
                            ->orWhereHas('activeMembers', fn ($m) => $m->where('user_id', $user->id));
                    });
            })
            ->withCount(['comments', 'attachments', 'subtasks'])
            ->withCount(['subtasks as subtasks_completed_count' => fn ($q) => $q->where('status', 'done')])
            ->with(['project', 'assignee', 'tags']);

        $tasks = QueryBuilder::for($query)
            ->allowedSorts('due_at', 'priority', 'position', 'created_at')
            ->defaultSort('position')
            ->allowedFilters(
                AllowedFilter::exact('project_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('assignee_id'),
                AllowedFilter::exact('priority'),
                AllowedFilter::scope('archived', 'withTrashed'),
            )
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $tasks->items(),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'total_pages' => $tasks->lastPage(),
                'has_more' => $tasks->hasMorePages(),
            ],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $task = Task::create($data);
        $task->load(['project', 'assignee', 'tags']);

        return response()->json([
            'success' => true,
            'data' => new TaskResource($task),
            'meta' => ['timestamp' => now()->toISOString()],
        ], 201);
    }

    public function show(Request $request, int $id): TaskResource
    {
        $task = Task::with(['project', 'assignee', 'tags', 'attachments', 'subtasks'])
            ->withCount(['comments', 'attachments', 'subtasks'])
            ->withCount(['subtasks as subtasks_completed_count' => fn ($q) => $q->where('status', 'done')])
            ->findOrFail($id);

        $this->authorize('view', $task);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, int $id): TaskResource
    {
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);

        $task->update($request->validated());
        $task->load(['project', 'assignee', 'tags']);

        return new TaskResource($task);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $task = Task::with('subtasks')->findOrFail($id);
        $this->authorize('delete', $task);

        // Cascade soft-delete subtasks recursively
        foreach ($task->subtasks as $subtask) {
            $subtask->delete();
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Задача удалена'],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    public function restore(Request $request, int $id): TaskResource
    {
        $task = Task::withTrashed()->findOrFail($id);
        $this->authorize('restore', $task);

        $task->restore();

        return new TaskResource($task);
    }

    public function complete(Request $request, int $id): TaskResource
    {
        $task = Task::findOrFail($id);
        $this->authorize('complete', $task);

        $this->completeTaskAction->execute($task);
        $task->refresh()->load(['project', 'assignee', 'tags']);

        return new TaskResource($task);
    }

    public function position(Request $request, int $id): TaskResource
    {
        $task = Task::findOrFail($id);
        $this->authorize('reorder', $task);

        $request->validate([
            'position' => ['nullable', 'integer'],
            'after_id' => ['nullable', 'integer', 'exists:tasks,id'],
        ]);

        $this->reorderTaskAction->execute(
            $task,
            $request->input('position'),
            $request->input('after_id')
        );

        return new TaskResource($task->fresh());
    }

    /**
     * GET /api/v1/tasks/{task}/subtasks
     */
    public function subtasks(Request $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $this->authorize('view', $task);

        $subtasks = $task->subtasks()
            ->with(['assignee', 'tags'])
            ->orderBy('position')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TaskResource::collection($subtasks),
        ]);
    }

    /**
     * POST /api/v1/tasks/{task}/subtasks
     */
    public function storeSubtask(Request $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:todo,in_progress,review,done'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'due_at' => ['nullable', 'date'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'position' => ['nullable', 'integer'],
        ]);

        $maxPosition = $task->subtasks()->max('position') ?? 0;
        $subtask = $task->subtasks()->create([
            ...$validated,
            'created_by' => $request->user()->id,
            'project_id' => $task->project_id,
            'position' => $validated['position'] ?? $maxPosition + 1000,
        ]);

        $subtask->load(['assignee', 'tags']);

        return response()->json([
            'success' => true,
            'data' => new TaskResource($subtask),
        ], 201);
    }

    /**
     * PUT /api/v1/tasks/{task}/subtasks/{subtask}
     */
    public function updateSubtask(Request $request, int $id, int $subtaskId): JsonResponse
    {
        $task = Task::findOrFail($id);
        $subtask = $task->subtasks()->findOrFail($subtaskId);
        $this->authorize('update', $subtask);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:todo,in_progress,review,done'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'due_at' => ['nullable', 'date'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'position' => ['nullable', 'integer'],
        ]);

        $subtask->update($validated);
        $subtask->load(['assignee', 'tags']);

        return response()->json([
            'success' => true,
            'data' => new TaskResource($subtask),
        ]);
    }

    /**
     * DELETE /api/v1/tasks/{task}/subtasks/{subtask}
     */
    public function destroySubtask(Request $request, int $id, int $subtaskId): JsonResponse
    {
        $task = Task::findOrFail($id);
        $subtask = $task->subtasks()->findOrFail($subtaskId);
        $this->authorize('delete', $subtask);

        $subtask->delete();

        return response()->json(['success' => true]);
    }
}
