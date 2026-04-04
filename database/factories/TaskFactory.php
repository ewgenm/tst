<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'project_id' => null,
            'parent_task_id' => null,
            'assignee_id' => null,
            'created_by' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::Medium,
            'due_at' => fake()->optional()->dateTimeBetween('now', '+1 month'),
            'position' => 0,
            'is_recurring' => false,
            'recurring_rule' => null,
        ];
    }

    public function inProject(Project $project): static
    {
        return $this->state(fn () => ['project_id' => $project->id]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn () => ['assignee_id' => $user->id]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn () => ['created_by' => $user->id]);
    }

    public function status(TaskStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function recurring(string $rule = 'FREQ=DAILY'): static
    {
        return $this->state(fn () => [
            'is_recurring' => true,
            'recurring_rule' => $rule,
            'due_at' => now()->addDays(7),
        ]);
    }

    public function asSubtask(Task $parent): static
    {
        return $this->state(fn () => [
            'parent_task_id' => $parent->id,
            'project_id' => $parent->project_id,
        ]);
    }
}
