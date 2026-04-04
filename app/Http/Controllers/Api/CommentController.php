<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Get all comments for a task.
     *
     * GET /api/v1/tasks/{task}/comments
     */
    public function index(int $task)
    {
        $taskModel = Task::findOrFail($task);

        $comments = $taskModel->comments()
            ->with(['user', 'attachments'])
            ->withCount('replies')
            ->whereNull('parent_comment_id')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($comments),
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * Create a comment.
     *
     * POST /api/v1/tasks/{task}/comments
     */
    public function store(Request $request, int $task): JsonResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'parent_comment_id' => ['nullable', 'exists:comments,id'],
        ]);

        $taskModel = Task::findOrFail($task);

        // Check authorization
        if ($taskModel->isInbox()) {
            if ($taskModel->created_by !== $request->user()->id
                && $taskModel->assignee_id !== $request->user()->id) {
                abort(403, 'Нет доступа к задаче');
            }
        } else {
            if ($taskModel->project->owner_id !== $request->user()->id
                && !$taskModel->project->isMember($request->user())) {
                abort(403, 'Нет доступа к задаче');
            }
        }

        $comment = Comment::create([
            'task_id' => $taskModel->id,
            'user_id' => $request->user()->id,
            'content' => $data['content'],
            'parent_comment_id' => $data['parent_comment_id'] ?? null,
        ]);

        $comment->load(['user']);

        return response()->json([
            'success' => true,
            'data' => new CommentResource($comment),
            'meta' => ['timestamp' => now()->toISOString()],
        ], 201);
    }

    /**
     * Update a comment.
     *
     * PUT /api/v1/comments/{comment}
     */
    public function update(Request $request, int $comment): CommentResource
    {
        $commentModel = Comment::findOrFail($comment);
        $this->authorize('update', $commentModel);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $commentModel->update(['content' => $data['content']]);
        $commentModel->load(['user']);

        return new CommentResource($commentModel);
    }

    /**
     * Delete a comment.
     *
     * DELETE /api/v1/comments/{comment}
     */
    public function destroy(Request $request, int $comment): JsonResponse
    {
        $commentModel = Comment::findOrFail($comment);
        $this->authorize('delete', $commentModel);

        $commentModel->delete();

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Комментарий удалён'],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * Get comment details.
     *
     * GET /api/v1/comments/{comment}
     */
    public function show(Request $request, int $comment): CommentResource
    {
        $commentModel = Comment::with(['user', 'attachments', 'replies'])
            ->withCount('replies')
            ->findOrFail($comment);

        $this->authorize('view', $commentModel);

        return new CommentResource($commentModel);
    }
}
