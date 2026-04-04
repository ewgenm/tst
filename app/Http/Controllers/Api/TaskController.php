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
            ->where(function ($q) use ($user) {
                // Inbox tasks
                $q->whereNull('project_id')
                    ->where(function ($sub) use ($user) {
                        $sub->where('created_by', $user->id)
                            ->orWhere('assignee_id', $user->id);
                    });
            })
            // Or tasks in projects user can access
            ->orWhereHas('project', function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('activeMembers', fn ($m) => $m->where('user_id', $user->id));
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
}
