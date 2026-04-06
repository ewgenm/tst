<?php

use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportImportController;
use App\Http\Controllers\Api\HabitController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ========================================
// Public routes (NO Sanctum - login, register, health)
// ========================================
Route::prefix('v1')->group(function () {
    // Auth (public)
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    
    Route::get('/auth/verify/{id}/{hash}', function ($id, $hash) {
        $user = \App\Models\User::findOrFail($id);
        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403, 'Invalid verification link');
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['success' => true, 'data' => ['message' => 'Email уже подтверждён']]);
        }
        $user->markEmailAsVerified();
        return response()->json(['success' => true, 'data' => ['message' => 'Email успешно подтверждён']]);
    })->name('verification.verify');
});

// Health check (public)
Route::get('/health', HealthController::class);

// ========================================
// Protected routes (require Sanctum token authentication)
// ========================================
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/me', [AuthController::class, 'updateProfile']);

    // Tasks
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);

    // Task Subtasks (ДО tasks/{task} для избежания конфликтов маршрутов)
    Route::get('/tasks/{task}/subtasks', [TaskController::class, 'subtasks']);
    Route::post('/tasks/{task}/subtasks', [TaskController::class, 'storeSubtask']);
    Route::put('/tasks/{task}/subtasks/{subtask}', [TaskController::class, 'updateSubtask']);
    Route::delete('/tasks/{task}/subtasks/{subtask}', [TaskController::class, 'destroySubtask']);

    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::post('/tasks/{task}/restore', [TaskController::class, 'restore']);
    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);
    Route::put('/tasks/{task}/position', [TaskController::class, 'position']);
    Route::patch('/tasks/{task}/move', [TaskController::class, 'move']);

    // Task Comments
    Route::get('/tasks/{task}/comments', [CommentController::class, 'index']);
    Route::post('/tasks/{task}/comments', [CommentController::class, 'store']);
    Route::get('/tasks/{task}/attachments', [AttachmentController::class, 'indexForTask']);
    Route::post('/tasks/{task}/attachments', [AttachmentController::class, 'storeForTask']);

    // Comments
    Route::get('/comments/{comment}', [CommentController::class, 'show']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    Route::get('/comments/{comment}/attachments', [AttachmentController::class, 'indexForComment']);
    Route::post('/comments/{comment}/attachments', [AttachmentController::class, 'storeForComment']);

    // Attachments
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);

    // Tags
    Route::get('/tags', [TagController::class, 'index']);
    Route::post('/tags', [TagController::class, 'store']);
    Route::put('/tags/{tag}', [TagController::class, 'update']);
    Route::delete('/tags/{tag}', [TagController::class, 'destroy']);

    // Habits
    Route::get('/habits', [HabitController::class, 'index']);
    Route::post('/habits', [HabitController::class, 'store']);
    Route::put('/habits/{habit}', [HabitController::class, 'update']);
    Route::delete('/habits/{habit}', [HabitController::class, 'destroy']);
    Route::post('/habits/{habit}/log', [HabitController::class, 'log']);
    Route::get('/habits/{habit}/stats', [HabitController::class, 'stats']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // Dashboard & Search
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/search', [SearchController::class, 'index']);

    // AI
    Route::post('/ai/generate-subtasks', [AiController::class, 'generateSubtasks']);
    Route::post('/ai/suggest-plan', [AiController::class, 'suggestPlan']);

    // Export/Import
    Route::get('/projects/{project}/export', [ExportImportController::class, 'exportProject']);
    Route::post('/import', [ExportImportController::class, 'import']);

    // Projects
    Route::apiResource('projects', ProjectController::class)->except(['destroy']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
    Route::post('/projects/{project}/restore', [ProjectController::class, 'restore']);
    Route::post('/projects/{project}/archive', [ProjectController::class, 'archive']);
    Route::delete('/projects/{project}/leave', [ProjectController::class, 'leave']);
    Route::post('/projects/{project}/invite', [ProjectController::class, 'invite']);
    Route::get('/projects/{project}/members', [ProjectController::class, 'members']);

    // Project members
    Route::patch('/project-members/{membership}/accept', [ProjectController::class, 'acceptInvite']);
    Route::delete('/project-members/{membership}', [ProjectController::class, 'removeMember']);
});
