<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Comment;
use App\Models\Task;
use App\Services\File\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttachmentController extends Controller
{
    public function __construct(
        protected FileUploadService $uploadService
    ) {
    }

    /**
     * Upload attachment for a task.
     *
     * POST /api/v1/tasks/{task}/attachments
     */
    public function storeForTask(Request $request, int $task): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,png,pdf,doc,docx,xls,xlsx,zip'],
        ]);

        $taskModel = Task::findOrFail($task);
        $this->checkTaskAccess($request, $taskModel);

        $attachment = $this->uploadService->upload(
            $request->file('file'),
            'App\Models\Task',
            $taskModel->id,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => new AttachmentResource($attachment),
            'meta' => ['timestamp' => now()->toISOString()],
        ], 201);
    }

    /**
     * Get attachments for a task.
     *
     * GET /api/v1/tasks/{task}/attachments
     */
    public function indexForTask(int $task): JsonResponse
    {
        $taskModel = Task::findOrFail($task);

        $attachments = $taskModel->attachments()->orderBy('created_at')->get();

        return response()->json([
            'success' => true,
            'data' => AttachmentResource::collection($attachments),
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * Upload attachment for a comment.
     *
     * POST /api/v1/comments/{comment}/attachments
     */
    public function storeForComment(Request $request, int $comment): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,png,pdf,doc,docx,xls,xlsx,zip'],
        ]);

        $commentModel = Comment::findOrFail($comment);
        $this->checkTaskAccess($request, $commentModel->task);

        $attachment = $this->uploadService->upload(
            $request->file('file'),
            'App\Models\Comment',
            $commentModel->id,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => new AttachmentResource($attachment),
            'meta' => ['timestamp' => now()->toISOString()],
        ], 201);
    }

    /**
     * Get attachments for a comment.
     *
     * GET /api/v1/comments/{comment}/attachments
     */
    public function indexForComment(int $comment): JsonResponse
    {
        $commentModel = Comment::findOrFail($comment);

        $attachments = $commentModel->attachments()->orderBy('created_at')->get();

        return response()->json([
            'success' => true,
            'data' => AttachmentResource::collection($attachments),
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * Delete an attachment.
     *
     * DELETE /api/v1/attachments/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $attachment = \App\Models\Attachment::findOrFail($id);

        // Only owner or task owner can delete
        if ($attachment->user_id !== $request->user()->id) {
            if ($attachment->attachable_type === 'App\Models\Task') {
                $task = Task::find($attachment->attachable_id);
                if ($task && $task->created_by !== $request->user()->id) {
                    abort(403, 'Нет прав для удаления');
                }
            }
        }

        $this->uploadService->delete($attachment);

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Вложение удалено'],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * Check if user can access the task.
     */
    private function checkTaskAccess(Request $request, Task $task): void
    {
        if ($task->isInbox()) {
            if ($task->created_by !== $request->user()->id
                && $task->assignee_id !== $request->user()->id) {
                abort(403, 'Нет доступа к задаче');
            }
        } else {
            if ($task->project->owner_id !== $request->user()->id
                && !$task->project->isMember($request->user())) {
                abort(403, 'Нет доступа к задаче');
            }
        }
    }
}
