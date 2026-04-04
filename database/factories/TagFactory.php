<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => null,
            'name' => fake()->word(),
            'color' => fake()->hexColor(),
        ];
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn () => ['project_id' => $project->id]);
    }
}
