<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfrastructureTest extends TestCase
{
    use RefreshDatabase;

    // ==================== HEALTH CHECK ====================

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'ok',
                    'services' => ['database' => 'up'],
                ],
            ]);
    }

    // ==================== EXPORT ====================

    public function test_user_can_export_project_as_json(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($user, 'owner')->create();
        Task::factory()->inProject($project)->createdBy($user)->count(3)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/projects/{$project->id}/export?format=json");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'project' => ['id', 'name'],
                    'tasks' => ['*' => ['id', 'title', 'status', 'priority']],
                ],
                'meta' => ['exported_at', 'total_tasks'],
            ]);
    }

    public function test_user_can_export_project_as_csv(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($user, 'owner')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/v1/projects/{$project->id}/export?format=csv");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_cannot_export_without_access(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create();

        $response = $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/projects/{$project->id}/export");

        $response->assertStatus(403);
    }

    // ==================== IMPORT ====================

    public function test_user_can_import_tasks(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $data = [
            ['title' => 'Imported Task 1', 'status' => 'todo', 'priority' => 'high'],
            ['title' => 'Imported Task 2', 'status' => 'in_progress', 'priority' => 'medium'],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/import', ['data' => $data]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['imported' => 2],
            ]);

        $this->assertDatabaseCount('tasks', 2);
    }

    public function test_import_requires_data(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/import', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data']);
    }

    // ==================== AI ====================

    public function test_user_can_generate_subtasks(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/ai/generate-subtasks', [
                'description' => 'Build a REST API',
                'count' => 3,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['subtasks' => ['*' => ['title', 'description', 'priority']]],
            ]);

        $this->assertCount(2, $response->json('data.subtasks'));
    }

    public function test_generate_subtasks_always_returns_success(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/ai/generate-subtasks', [
                'description' => 'Test',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_user_can_suggest_plan(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/ai/suggest-plan', [
                'goal' => 'Launch a mobile app',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['plan' => ['summary', 'steps']],
            ]);
    }
}
