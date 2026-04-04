<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommentsTagsAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    // ==================== COMMENTS ====================

    public function test_user_can_create_comment_on_task(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/comments", [
                'content' => 'This is a comment',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'content' => 'This is a comment',
                    'user_id' => $user->id,
                ],
            ]);
    }

    public function test_user_can_reply_to_comment(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)->create();
        $comment = Comment::factory()->for($task)->for($user, 'user')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/comments", [
                'content' => 'This is a reply',
                'parent_comment_id' => $comment->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'content' => 'This is a reply',
                    'parent_comment_id' => $comment->id,
                ],
            ]);
    }

    public function test_user_can_view_task_comments(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)->create();
        Comment::factory()->count(3)->for($task)->for($user, 'user')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/tasks/{$task->id}/comments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['*' => ['id', 'content', 'user_id', 'created_at']],
            ]);
    }

    public function test_user_can_update_own_comment(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $comment = Comment::factory()->for($user, 'user')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/comments/{$comment->id}", [
                'content' => 'Updated content',
            ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['content' => 'Updated content']]);
    }

    public function test_user_can_delete_own_comment(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $comment = Comment::factory()->for($user, 'user')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    // ==================== TAGS ====================

    public function test_user_can_create_tag(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tags', [
                'name' => 'urgent',
                'color' => '#FF0000',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'urgent',
                    'color' => '#FF0000',
                ],
            ]);
    }

    public function test_user_can_list_their_tags(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Tag::factory()->count(3)->for($user, 'user')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tags');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['*' => ['id', 'name', 'color']],
            ]);
    }

    public function test_user_can_update_own_tag(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tag = Tag::factory()->for($user, 'user')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/tags/{$tag->id}", [
                'name' => 'updated',
                'color' => '#00FF00',
            ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['name' => 'updated', 'color' => '#00FF00']]);
    }

    public function test_user_can_delete_own_tag(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tag = Tag::factory()->for($user, 'user')->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/tags/{$tag->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_project_admin_can_manage_project_tags(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create();
        ProjectMember::factory()->for($project)->for($admin, 'user')->admin()->create();
        $tag = Tag::factory()->forProject($project)->for($admin, 'user')->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/tags/{$tag->id}", ['name' => 'Admin Updated']);

        $response->assertStatus(200);
    }

    public function test_cannot_create_duplicate_tag_in_project(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->for($user, 'owner')->create();
        Tag::factory()->forProject($project)->for($user, 'user')->create(['name' => 'duplicate']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tags', [
                'name' => 'duplicate',
                'project_id' => $project->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR'],
            ]);
    }

    // ==================== ATTACHMENTS ====================

    public function test_user_can_upload_attachment_to_task(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)->create();

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'filename' => 'document.pdf',
                    'mime_type' => 'application/pdf',
                ],
            ]);

        Storage::disk('local')->assertExists('attachments/' . $file->hashName());
    }

    public function test_user_can_view_task_attachments(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)->create();

        $file = UploadedFile::fake()->create('image.jpg', 50, 'image/jpeg');
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/attachments", ['file' => $file]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/tasks/{$task->id}/attachments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['*' => ['id', 'filename', 'mime_type', 'size']],
            ]);
    }

    public function test_user_can_delete_attachment(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)->create();

        $file = UploadedFile::fake()->create('delete.pdf', 100, 'application/pdf');
        $uploadResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/attachments", ['file' => $file]);

        $attachmentId = $uploadResponse->json('data.id');

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/attachments/{$attachmentId}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('attachments', ['id' => $attachmentId]);
    }

    public function test_cannot_upload_invalid_file_type(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)->create();

        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_cannot_upload_oversized_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = Task::factory()->createdBy($user)->create();

        $file = UploadedFile::fake()->create('huge.pdf', 20000, 'application/pdf');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }
}
