<?php

namespace Database\Factories;

use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HabitFactory extends Factory
{
    protected $model = Habit::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word(),
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(['💪', '📚', '🏃', '💧', null]),
            'frequency' => fake()->randomElement(['daily', 'weekly', 'custom']),
            'target_days' => [1, 2, 3, 4, 5], // Mon-Fri
            'current_streak' => 0,
            'best_streak' => 0,
        ];
    }

    public function daily(): static
    {
        return $this->state(fn () => ['frequency' => 'daily']);
    }

    public function withStreak(int $streak): static
    {
        return $this->state(fn () => [
            'current_streak' => $streak,
            'best_streak' => $streak,
        ]);
    }
}
