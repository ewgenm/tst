<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Project::class => ProjectPolicy::class,
        ProjectMember::class => ProjectPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Это вызывает registerPolicies() и загружает $policies mapping
        $this->registerPolicies();

        // Дополнительная явная регистрация (на всякий случай)
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(ProjectMember::class, ProjectPolicy::class);
    }
}
