<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportImportController extends Controller
{
    /**
     * GET /api/v1/projects/{id}/export?format=json
     */
    public function exportProject(Request $request, int $project): \Symfony\Component\HttpFoundation\Response
    {
        $projectModel = Project::with(['tasks.tags', 'tasks.comments', 'tasks.attachments'])
            ->findOrFail($project);

        if ($projectModel->owner_id !== $request->user()->id
            && !$projectModel->isMember($request->user())) {
            abort(403, 'Нет доступа');
        }

        $format = $request->input('format', 'json');

        if ($format === 'csv') {
            return $this->exportToCsv($projectModel);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'project' => [
                    'id' => $projectModel->id,
                    'name' => $projectModel->name,
                    'description' => $projectModel->description,
                    'color' => $projectModel->color,
                ],
                'tasks' => $projectModel->tasks->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'status' => $t->status->value,
                    'priority' => $t->priority->value,
                    'due_at' => $t->due_at?->toISOString(),
                    'tags' => $t->tags->pluck('name'),
                    'comments_count' => $t->comments->count(),
                ]),
            ],
            'meta' => [
                'exported_at' => now()->toISOString(),
                'total_tasks' => $projectModel->tasks->count(),
            ],
        ]);
    }

    private function exportToCsv(Project $project): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tasks.csv"',
        ];

        $callback = function () use ($project) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Title', 'Status', 'Priority', 'Due At', 'Tags']);

            foreach ($project->tasks as $task) {
                fputcsv($file, [
                    $task->id,
                    $task->title,
                    $task->status->value,
                    $task->priority->value,
                    $task->due_at?->toISOString() ?? '',
                    $task->tags->pluck('name')->join('; '),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * POST /api/v1/import
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'data' => ['required', 'array'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $user = $request->user();
        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($request, $user, &$imported, &$errors) {
            foreach ($request->input('data') as $taskData) {
                try {
                    if (!isset($taskData['title'])) {
                        $errors[] = 'Missing title';
                        continue;
                    }

                    Task::create([
                        'title' => $taskData['title'],
                        'description' => $taskData['description'] ?? null,
                        'status' => $taskData['status'] ?? 'todo',
                        'priority' => $taskData['priority'] ?? 'medium',
                        'due_at' => $taskData['due_at'] ?? null,
                        'project_id' => $request->input('project_id'),
                        'created_by' => $user->id,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        });

        return response()->json([
            'success' => true,
            'data' => [
                'imported' => $imported,
                'errors' => $errors,
            ],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }
}
