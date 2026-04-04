<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Events\ProjectMemberAdded;
use Illuminate\Support\Facades\DB;

/**
 * Приглашение пользователя в проект.
 * 
 * Создаёт ProjectMember со status='pending',
 * создаёт Notification (type=project_invite).
 */
class InviteMemberAction
{
    /**
     * @param Project $project
     * @param User $userToAdd
     * @param string $role 'admin'|'member'|'viewer'
     * @param User $invitedBy
     * @return ProjectMember
     * 
     * @throws \Exception если пользователь уже участник
     */
    public function execute(
        Project $project,
        User $userToAdd,
        string $role,
        User $invitedBy
    ): ProjectMember {
        return DB::transaction(function () use ($project, $userToAdd, $role, $invitedBy) {
            // Проверка на существующее членство
            $existingMembership = ProjectMember::where('project_id', $project->id)
                ->where('user_id', $userToAdd->id)
                ->first();

            if ($existingMembership) {
                if ($existingMembership->isActive()) {
                    throw new \Exception('Пользователь уже является участником проекта');
                }

                // Обновляем существующий pending инвайт
                $existingMembership->update([
                    'role' => $role,
                    'invited_by' => $invitedBy->id,
                    'invited_at' => now(),
                ]);

                return $existingMembership->refresh();
            }

            // Создаём новое pending членство
            $membership = ProjectMember::create([
                'project_id' => $project->id,
                'user_id' => $userToAdd->id,
                'role' => $role,
                'status' => 'pending',
                'invited_by' => $invitedBy->id,
                'invited_at' => now(),
            ]);

            // Создаём уведомление для приглашённого
            $userToAdd->notifications()->create([
                'type' => 'project_invite',
                'title' => 'Приглашение в проект',
                'message' => "Вас пригласили в проект \"{$project->name}\"",
                'data' => [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'membership_id' => $membership->id,
                    'role' => $role,
                ],
            ]);

            // Событие будет добавлено когда настроим Reverb
            // ProjectMemberAdded::dispatch($membership);

            return $membership;
        });
    }
}
