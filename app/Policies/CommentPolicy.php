<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Determine whether the user can view any comments.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the comment.
     */
    public function view(User $user, Comment $comment): bool
    {
        // Can view if user can access the task
        $task = $comment->task;

        if ($task->isInbox()) {
            return $task->created_by === $user->id
                || $task->assignee_id === $user->id;
        }

        return $task->project->owner_id === $user->id
            || $task->project->isMember($user);
    }

    /**
     * Determine whether the user can create comments.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the comment.
     * Author or project owner/admin can update.
     */
    public function update(User $user, Comment $comment): bool
    {
        if ($comment->isAuthor($user)) {
            return true;
        }

        return $this->isTaskOwnerOrAdmin($user, $comment->task);
    }

    /**
     * Determine whether the user can delete the comment.
     * Author or project owner/admin can delete.
     */
    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->isAuthor($user)) {
            return true;
        }

        return $this->isTaskOwnerOrAdmin($user, $comment->task);
    }

    /**
     * Check if user is task owner or project admin.
     */
    private function isTaskOwnerOrAdmin(User $user, $task): bool
    {
        if (!$task->project) {
            return false;
        }

        $project = $task->project;

        return $project->owner_id === $user->id
            || $project->isAdmin($user);
    }
}
