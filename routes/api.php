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
|
| Здесь регистрируются все API маршруты для приложения.
 | Маршруты используют группу 'api' middleware (CORS, JSON).
 | Sanctum middleware будет добавлен после установки пакета.
 |
*/

Route::prefix('v1')->group(function () {
    
    // ========================================
    // 3.1. Auth & Profile (public)
    // ========================================
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // ========================================
    // Protected routes (require authentication)
    // ========================================
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/me', [AuthController::class, 'updateProfile']);
        });

        // 3.2. Dashboard & Search
        // Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        // Route::get('/search', [SearchController::class, 'index']);

        // 3.3. Tasks
        Route::get('/tasks', [TaskController::class, 'index']);
        Route::post('/tasks', [TaskController::class, 'store']);
        Route::get('/tasks/{task}', [TaskController::class, 'show']);
        Route::put('/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
        Route::post('/tasks/{task}/restore', [TaskController::class, 'restore']);
        Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);
        Route::put('/tasks/{task}/position', [TaskController::class, 'position']);

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

        // 3.6. Tags
        Route::get('/tags', [TagController::class, 'index']);
        Route::post('/tags', [TagController::class, 'store']);
        Route::put('/tags/{tag}', [TagController::class, 'update']);
        Route::delete('/tags/{tag}', [TagController::class, 'destroy']);

        // 3.5. Habits
        Route::get('/habits', [HabitController::class, 'index']);
        Route::post('/habits', [HabitController::class, 'store']);
        Route::put('/habits/{habit}', [HabitController::class, 'update']);
        Route::delete('/habits/{habit}', [HabitController::class, 'destroy']);
        Route::post('/habits/{habit}/log', [HabitController::class, 'log']);
        Route::get('/habits/{habit}/stats', [HabitController::class, 'stats']);

        // 3.7. Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

        // 3.2. Dashboard & Search
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/search', [SearchController::class, 'index']);

        // 3.8. AI
        Route::post('/ai/generate-subtasks', [AiController::class, 'generateSubtasks']);
        Route::post('/ai/suggest-plan', [AiController::class, 'suggestPlan']);

        // 3.9. Export/Import
        Route::get('/projects/{project}/export', [ExportImportController::class, 'exportProject']);
        Route::post('/import', [ExportImportController::class, 'import']);

        // 3.4. Projects
        Route::apiResource('projects', ProjectController::class)->except(['destroy']);
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
        Route::post('/projects/{project}/restore', [ProjectController::class, 'restore']);
        Route::post('/projects/{project}/archive', [ProjectController::class, 'archive']);
        Route::delete('/projects/{project}/leave', [ProjectController::class, 'leave']);
        Route::post('/projects/{project}/invite', [ProjectController::class, 'invite']);
        Route::get('/projects/{project}/members', [ProjectController::class, 'members']);
        
        // Project members (accept invite, remove)
        Route::patch('/project-members/{membership}/accept', [ProjectController::class, 'acceptInvite']);
        Route::delete('/project-members/{membership}', [ProjectController::class, 'removeMember']);

        // 3.5. Habits
        // Route::apiResource('habits', HabitController::class);

        // 3.6. Tags
        // Route::apiResource('tags', TagController::class);

        // 3.7. Notifications
        // Route::get('/notifications', [NotificationController::class, 'index']);

        // 3.8. Integrations & AI
        // ... integration routes

        // 3.9. Export/Import
        // ... export routes
    });
});

// Health check (public)
Route::get('/health', HealthController::class);
