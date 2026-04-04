<?php

namespace App\Actions\Project;

use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Принятие приглашения в проект.
 * 
 * Меняет status с 'pending' на 'active'.
 */
class AcceptInviteAction
{
    /**
     * @param ProjectMember $membership
     * @param User $user
     * @return ProjectMember
     * 
     * @throws \Exception если пользователь не может принять приглашение
     */
    public function execute(ProjectMember $membership, User $user): ProjectMember
    {
        // Проверка что это приглашение именно этому пользователю
        if ($membership->user_id !== $user->id) {
            throw new \Exception('Вы не можете принять это приглашение');
        }

        // Проверка что статус pending
        if (!$membership->isPending()) {
            throw new \Exception('Приглашение уже принято или отклонено');
        }

        return DB::transaction(function () use ($membership) {
            $membership->update([
                'status' => 'active',
            ]);

            // Отмечаем уведомление как прочитанное
            $user = $membership->user;
            $user->notifications()
                ->where('type', 'project_invite')
                ->whereJsonContains('data->membership_id', (string) $membership->id)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            return $membership->refresh();
        });
    }
}
