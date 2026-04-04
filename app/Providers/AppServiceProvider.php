<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Tag;
use App\Models\Task;
use App\Policies\CommentPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\TagPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Явная регистрация политик
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(ProjectMember::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);

        // Route model binding для ProjectMember
        Route::model('membership', ProjectMember::class);
    }
}
