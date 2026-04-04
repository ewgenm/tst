<?php

namespace Tests\Feature;

use App\Models\Habit;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HabitsDashboardSearchNotificationsTest extends TestCase
{
    use RefreshDatabase;

    // ==================== HABITS ====================

    public function test_user_can_create_habit(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/habits', [
                'name' => 'Morning Exercise',
                'color' => '#8B5CF6',
                'icon' => '💪',
                'frequency' => 'daily',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Morning Exercise',
                    'color' => '#8B5CF6',
                ],
            ]);
    }

    public function test_user_can_list_their_habits(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Habit::factory()->count(3)->for($user, 'user')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/habits');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['*' => ['id', 'name', 'color', 'current_streak']],
            ]);
    }

    public function test_user_can_update_own_habit(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $habit = Habit::factory()->for($user, 'user')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/habits/{$habit->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Updated Name']]);
    }

    public function test_user_can_delete_own_habit(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $habit = Habit::factory()->for($user, 'user')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/habits/{$habit->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('habits', ['id' => $habit->id]);
    }

    public function test_user_can_log_habit_completion(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $habit = Habit::factory()->for($user, 'user')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/habits/{$habit->id}/log");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'current_streak' => 1,
                    'best_streak' => 1,
                ],
            ]);
    }

    public function test_cannot_log_future_habit(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $habit = Habit::factory()->for($user, 'user')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/habits/{$habit->id}/log", [
                'date' => now()->addDay()->format('Y-m-d'),
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_duplicate_habit_log(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $habit = Habit::factory()->for($user, 'user')->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/habits/{$habit->id}/log");

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/habits/{$habit->id}/log");

        $response->assertStatus(422);
    }

    public function test_habit_streak_increments(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $habit = Habit::factory()->for($user, 'user')->create();

        // Day 1
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/habits/{$habit->id}/log", ['date' => now()->subDay()->format('Y-m-d')]);

        // Day 2 (today)
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/habits/{$habit->id}/log");

        $habit->refresh();
        $this->assertEquals(2, $habit->current_streak);
    }

    public function test_user_can_view_habit_stats(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $habit = Habit::factory()->for($user, 'user')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/habits/{$habit->id}/stats");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['current_streak', 'best_streak', 'completion_rate', 'total_completions'],
            ]);
    }

    // ==================== DASHBOARD ====================

    public function test_user_can_view_dashboard_stats(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Task::factory()->count(3)->createdBy($user)->create();
        Habit::factory()->for($user, 'user')->withStreak(5)->create();
        Project::factory()->for($user, 'owner')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'tasks_by_status',
                    'overdue_tasks',
                    'habits_streaks',
                    'projects_count',
                ],
            ]);
    }

    // ==================== SEARCH ====================

    public function test_user_can_search_tasks(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Task::factory()->createdBy($user)->create(['title' => 'Find this task']);
        Task::factory()->createdBy($user)->create(['title' => 'Other task']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/search?q=Find');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['tasks', 'projects', 'habits'],
            ]);

        $this->assertCount(1, $response->json('data.tasks'));
    }

    public function test_user_can_search_projects(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Project::factory()->for($user, 'owner')->create(['name' => 'My Project']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/search?q=My&type=projects');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.projects'));
    }

    public function test_search_requires_query(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    // ==================== NOTIFICATIONS ====================

    public function test_user_can_list_notifications(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Notification::factory()->count(3)->for($user, 'user')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['*' => ['id', 'type', 'title', 'message']],
                'pagination',
            ]);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $notification = Notification::factory()->for($user, 'user')->create(['is_read' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'is_read' => true]);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Notification::factory()->count(3)->for($user, 'user')->create(['is_read' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/notifications/read-all');

        $response->assertStatus(200);
        $this->assertEquals(0, $user->notifications()->where('is_read', false)->count());
    }

    public function test_user_can_get_unread_count(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Notification::factory()->count(3)->for($user, 'user')->create(['is_read' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['count' => 3]]);
    }
}
