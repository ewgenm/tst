<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_task(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks', [
                'title' => 'New Task',
                'status' => TaskStatus::Todo->value,
                'priority' => TaskPriority::Medium->value,
            ]);

        if ($response->status() === 500) {
            dump($response->json('message') ?? $response->json());
        }

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'New Task',
                    'status' => 'todo',
                    'created_by' => $user->id,
                ],
            ]);
    }

    public function test_user_can_create_task_in_project(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($user, 'owner')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks', [
                'title' => 'Project Task',
                'project_id' => $project->id,
                'status' => TaskStatus::Todo->value,
                'priority' => TaskPriority::High->value,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => 'Project Task',
                    'project_id' => $project->id,
                    'priority' => 'high',
                ],
            ]);
    }

    public function test_user_can_view_task(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => $task->id],
            ]);
    }

    public function test_user_cannot_view_others_task(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($other)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_own_task(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/tasks/{$task->id}", [
                'title' => 'Updated Title',
                'priority' => TaskPriority::Urgent->value,
            ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['title' => 'Updated Title', 'priority' => 'urgent']]);
    }

    public function test_user_cannot_update_others_task(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($other)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/tasks/{$task->id}", ['title' => 'Hacked']);

        $response->assertStatus(403);
    }

    public function test_project_admin_can_update_any_task(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create();
        ProjectMember::factory()->for($project)->for($admin, 'user')->admin()->create();
        $task = Task::factory()->inProject($project)->createdBy($owner)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/tasks/{$task->id}", ['title' => 'Admin Update']);

        $response->assertStatus(200);
    }

    public function test_user_can_delete_own_task(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_user_can_complete_task(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/complete");

        $response->assertStatus(200)
            ->assertJson(['data' => ['status' => 'done']]);
    }

    public function test_completing_recurring_task_creates_copy(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)
            ->recurring('FREQ=DAILY')
            ->create(['due_at' => now()->addDays(7)]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/complete")
            ->assertStatus(200);

        // Original is done
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'done']);

        // New copy created
        $this->assertDatabaseHas('tasks', [
            'title' => $task->title,
            'status' => 'todo',
            'is_recurring' => true,
        ]);
    }

    public function test_cannot_complete_already_completed_task(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)->status(TaskStatus::Done)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/complete");

        $response->assertStatus(500); // Exception thrown
    }

    public function test_user_can_reorder_task(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)->create(['position' => 0]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/tasks/{$task->id}/position", ['position' => 5]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['position' => 5]]);
    }

    public function test_subtasks_are_cascade_deleted(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $parent = Task::factory()->createdBy($user)->create();
        $subtask = Task::factory()->asSubtask($parent)->createdBy($user)->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/tasks/{$parent->id}")
            ->assertStatus(200);

        // Parent soft-deleted
        $this->assertSoftDeleted('tasks', ['id' => $parent->id]);
        
        // Subtask also soft-deleted (cascade soft-delete)
        $this->assertSoftDeleted('tasks', ['id' => $subtask->id]);
    }

    public function test_user_can_list_tasks(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Task::factory()->count(3)->createdBy($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['*' => ['id', 'title', 'status', 'priority']],
                'pagination' => ['current_page', 'per_page', 'total'],
            ]);
    }

    public function test_create_task_with_invalid_status_returns_error(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks', [
                'title' => 'Bad Task',
                'status' => 'invalid_status',
                'priority' => TaskPriority::Medium->value,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_create_task_with_invalid_recurring_rule(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks', [
                'title' => 'Bad Task',
                'status' => TaskStatus::Todo->value,
                'priority' => TaskPriority::Medium->value,
                'is_recurring' => true,
                'recurring_rule' => 'INVALID',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['recurring_rule']);
    }
}
