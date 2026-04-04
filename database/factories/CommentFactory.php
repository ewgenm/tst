<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'parent_comment_id' => null,
            'content' => fake()->paragraph(),
        ];
    }

    public function asReply(Comment $parent): static
    {
        return $this->state(fn () => [
            'task_id' => $parent->task_id,
            'parent_comment_id' => $parent->id,
        ]);
    }
}
