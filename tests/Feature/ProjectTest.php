<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Пользователь может создать проект.
     */
    public function test_user_can_create_project(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/projects', [
                'name' => 'Test Project',
                'description' => 'A test project',
                'color' => '#FF5733',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Test Project',
                    'color' => '#FF5733',
                    'owner_id' => $user->id,
                ],
            ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'owner_id' => $user->id,
        ]);

        // Владелец автоматически добавляется как admin
        $this->assertDatabaseHas('project_members', [
            'project_id' => Project::where('name', 'Test Project')->first()->id,
            'user_id' => $user->id,
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    /**
     * Создание проекта без имени возвращает ошибку.
     */
    public function test_create_project_without_name_returns_error(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/projects', [
                'description' => 'No name',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Пользователь может получить свои проекты.
     */
    public function test_user_can_list_their_projects(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Project::factory()->count(3)->for($user, 'owner')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/projects');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'color', 'is_favorite', 'is_archived', 'sort_order'],
                ],
                'pagination' => ['current_page', 'per_page', 'total', 'total_pages', 'has_more'],
                'meta' => ['timestamp'],
            ]);
    }

    /**
     * Пользователь может просмотреть детали проекта.
     */
    public function test_user_can_view_project(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($user, 'owner')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'owner_id' => $user->id,
                ],
            ]);
    }

    /**
     * Пользователь НЕ может просмотреть чужой проект.
     */
    public function test_user_cannot_view_other_users_project(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $viewer = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create();

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(403);
    }

    /**
     * Владелец может обновить проект.
     */
    public function test_owner_can_update_project(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($user, 'owner')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/projects/{$project->id}", [
                'name' => 'Updated Name',
                'color' => '#000000',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Name',
                    'color' => '#000000',
                ],
            ]);
    }

    /**
     * Только владелец может удалить проект.
     */
    public function test_only_owner_can_delete_project(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create();

        // Добавляем участника с ролью admin
        ProjectMember::factory()->for($project)->for($member, 'user')->admin()->create();

        // Admin НЕ может удалить
        $response = $this->actingAs($member, 'sanctum')
            ->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(403);

        // Владелец МОЖЕТ удалить
        $response = $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    /**
     * Владелец или Admin могут архивировать проект.
     */
    public function test_admin_can_archive_project(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create();

        ProjectMember::factory()->for($project)->for($admin, 'user')->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/projects/{$project->id}/archive");

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['is_archived' => true],
            ]);
    }

    /**
     * Member НЕ может архивировать проект.
     */
    public function test_member_cannot_archive_project(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create();

        ProjectMember::factory()->for($project)->for($member, 'user')->create(['role' => 'member']);

        $response = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/projects/{$project->id}/archive");

        $response->assertStatus(403);
    }

    /**
     * Владелец может пригласить участника.
     */
    public function test_owner_can_invite_member(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $invitee = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/projects/{$project->id}/invite", [
                'email' => $invitee->email,
                'role' => 'member',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => $invitee->id,
                    'role' => 'member',
                    'status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $invitee->id,
            'status' => 'pending',
        ]);

        // Проверка создания уведомления
        $this->assertDatabaseHas('notifications', [
            'user_id' => $invitee->id,
            'type' => 'project_invite',
        ]);
    }

    /**
     * Member НЕ может пригласить участника.
     */
    public function test_member_cannot_invite(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $newUser = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create();

        ProjectMember::factory()->for($project)->for($member, 'user')->create(['role' => 'member']);

        $response = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/projects/{$project->id}/invite", [
                'email' => $newUser->email,
                'role' => 'member',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Приглашённый может принять приглашение.
     */
    public function test_user_can_accept_invite(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $invitee = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create();

        $membership = ProjectMember::factory()
            ->for($project)
            ->for($invitee, 'user')
            ->pending()
            ->create();

        $response = $this->actingAs($invitee, 'sanctum')
            ->patchJson("/api/v1/project-members/{$membership->id}/accept");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'active',
                ],
            ]);

        $this->assertDatabaseHas('project_members', [
            'id' => $membership->id,
            'status' => 'active',
        ]);
    }

    /**
     * Пользователь НЕ может принять чужое приглашение.
     */
    public function test_user_cannot_accept_others_invite(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $invitee = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create();

        $membership = ProjectMember::factory()
            ->for($project)
            ->for($invitee, 'user')
            ->pending()
            ->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->patchJson("/api/v1/project-members/{$membership->id}/accept");

        $response->assertStatus(403);
    }

    /**
     * Пользователь может покинуть проект.
     */
    public function test_user_can_leave_project(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create();

        ProjectMember::factory()->for($project)->for($member, 'user')->create();

        $response = $this->actingAs($member, 'sanctum')
            ->deleteJson("/api/v1/projects/{$project->id}/leave");

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['message' => 'Вы покинули проект'],
            ]);

        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);
    }

    /**
     * Владелец НЕ может покинуть проект (может только удалить).
     */
    public function test_owner_cannot_leave_project(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create();

        $response = $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/projects/{$project->id}/leave");

        $response->assertStatus(403);
    }

    /**
     * Получить список участников проекта.
     */
    public function test_can_list_project_members(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create();

        ProjectMember::factory()->for($project)->for($member, 'user')->create();

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/projects/{$project->id}/members");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'user_id', 'role', 'status'],
                ],
                'meta' => ['timestamp'],
            ]);
    }

    /**
     * Фильтрация проектов (archived).
     */
    public function test_can_filter_archived_projects(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Project::factory()->for($user, 'owner')->create();
        Project::factory()->for($user, 'owner')->archived()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/projects?filter[archived]=0');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Все returned projects should NOT be archived
        foreach ($data as $project) {
            $this->assertFalse($project['is_archived']);
        }
    }
}
