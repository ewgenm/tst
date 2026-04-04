<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view any tasks.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task): bool
    {
        // Inbox task (no project): author or assignee can view
        if ($task->isInbox()) {
            return $task->created_by === $user->id
                || $task->assignee_id === $user->id;
        }

        // Task in project: project owner/member can view
        return $this->canAccessProject($user, $task->project);
    }

    /**
     * Determine whether the user can create tasks.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create tasks
    }

    /**
     * Determine whether the user can update the task.
     * Author can edit own task, project owner/admin can edit any.
     */
    public function update(User $user, Task $task): bool
    {
        // Author can edit own task
        if ($task->created_by === $user->id) {
            return true;
        }

        // Project owner or admin can edit any task
        if ($task->project) {
            return $this->isProjectOwnerOrAdmin($user, $task->project);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task): bool
    {
        // Author can delete own task
        if ($task->created_by === $user->id) {
            return true;
        }

        // Project owner or admin can delete any task
        if ($task->project) {
            return $this->isProjectOwnerOrAdmin($user, $task->project);
        }

        return false;
    }

    /**
     * Determine whether the user can restore the task.
     */
    public function restore(User $user, Task $task): bool
    {
        return $this->delete($user, $task);
    }

    /**
     * Determine whether the user can complete the task.
     */
    public function complete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * Determine whether the user can reorder the task.
     */
    public function reorder(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * Determine whether the user can attach tags to the task.
     */
    public function attachTag(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * Determine whether the user can detach tags from the task.
     */
    public function detachTag(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * Check if user can access the project.
     */
    private function canAccessProject(User $user, ?Project $project): bool
    {
        if (!$project) {
            return false;
        }

        return $project->owner_id === $user->id
            || $project->isMember($user);
    }

    /**
     * Check if user is project owner or admin.
     */
    private function isProjectOwnerOrAdmin(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id
            || $project->isAdmin($user);
    }
}
