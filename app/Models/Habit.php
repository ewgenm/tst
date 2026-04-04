<?php

namespace App\Models;

use Database\Factories\HabitFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(HabitFactory::class)]
class Habit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'color',
        'icon',
        'frequency',
        'target_days',
        'current_streak',
        'best_streak',
        'last_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_days' => 'array',
            'current_streak' => 'integer',
            'best_streak' => 'integer',
            'last_completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(HabitCompletion::class);
    }

    public function isCompletedToday(): bool
    {
        return $this->completions()
            ->whereDate('completed_date', today())
            ->exists();
    }

    public function getCompletionRate(int $days = 30): float
    {
        $totalCompletions = $this->completions()
            ->where('completed_date', '>=', now()->subDays($days))
            ->count();

        return $totalCompletions / $days;
    }
}
