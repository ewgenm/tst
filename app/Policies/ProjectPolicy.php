<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return true; // Любой аутентифицированный пользователь может видеть свои проекты
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        // Временно: разрешить всем
        return true;
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        return true; // Любой аутентифицированный может создавать проекты
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        // Владелец ИЛИ Admin
        return $project->owner_id === $user->id
            || $project->isAdmin($user);
    }

    /**
     * Determine whether the user can delete the project.
     * ТОЛЬКО владелец может удалить проект.
     */
    public function delete(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id;
    }

    /**
     * Determine whether the user can archive the project.
     * Владелец ИЛИ Admin
     */
    public function archive(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id
            || $project->isAdmin($user);
    }

    /**
     * Determine whether the user can restore the project.
     * ТОЛЬКО владелец
     */
    public function restore(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id;
    }

    /**
     * Determine whether the user can invite members.
     * Владелец ИЛИ Admin
     */
    public function invite(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id
            || $project->isAdmin($user);
    }

    /**
     * Determine whether the user can remove a member.
     * Владелец ИЛИ Admin (но не может удалить владельца)
     */
    public function removeMember(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id
            || $project->isAdmin($user);
    }

    /**
     * Determine whether the user can accept the invite.
     * Только приглашённый пользователь
     */
    public function acceptInvite(User $user, ProjectMember $membership): bool
    {
        return $membership->user_id === $user->id;
    }

    /**
     * Determine whether the user can leave the project.
     * Любой участник (но не владелец)
     */
    public function leave(User $user, Project $project): bool
    {
        // Владелец не может "покинуть" проект — он может только удалить его
        return $project->owner_id !== $user->id
            && $project->isMember($user);
    }
}
