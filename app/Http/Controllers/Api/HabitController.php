<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Habit;
use App\Models\HabitCompletion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HabitController extends Controller
{
    /**
     * GET /api/v1/habits
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $habits = $user->habits()
            ->withCount(['completions as completions_count' => function ($q) {
                $q->where('completed_date', '>=', now()->subDays(30));
            }])
            ->orderBy('current_streak', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $habits,
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * POST /api/v1/habits
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'regex:/^#[0-9A-F]{6}$/i'],
            'icon' => ['nullable', 'string', 'max:50'],
            'frequency' => ['sometimes', 'in:daily,weekly,custom'],
            'target_days' => ['nullable', 'array'],
        ]);

        $habit = $request->user()->habits()->create([
            'name' => $data['name'],
            'color' => $data['color'] ?? '#8B5CF6',
            'icon' => $data['icon'] ?? null,
            'frequency' => $data['frequency'] ?? 'daily',
            'target_days' => $data['target_days'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $habit,
            'meta' => ['timestamp' => now()->toISOString()],
        ], 201);
    }

    /**
     * PUT /api/v1/habits/{habit}
     */
    public function update(Request $request, int $habit): JsonResponse
    {
        $habitModel = Habit::findOrFail($habit);

        if ($habitModel->user_id !== $request->user()->id) {
            abort(403, 'Нет доступа');
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'color' => ['nullable', 'regex:/^#[0-9A-F]{6}$/i'],
            'icon' => ['nullable', 'string', 'max:50'],
            'frequency' => ['sometimes', 'in:daily,weekly,custom'],
            'target_days' => ['nullable', 'array'],
        ]);

        $habitModel->update($data);

        return response()->json([
            'success' => true,
            'data' => $habitModel,
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * DELETE /api/v1/habits/{habit}
     */
    public function destroy(Request $request, int $habit): JsonResponse
    {
        $habitModel = Habit::findOrFail($habit);

        if ($habitModel->user_id !== $request->user()->id) {
            abort(403, 'Нет доступа');
        }

        $habitModel->delete();

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Привычка удалена'],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * POST /api/v1/habits/{habit}/log
     */
    public function log(Request $request, int $habit): JsonResponse
    {
        $habitModel = Habit::findOrFail($habit);

        if ($habitModel->user_id !== $request->user()->id) {
            abort(403, 'Нет доступа');
        }

        $date = $request->input('date') ? Carbon::parse($request->input('date')) : now();

        // Не разрешаем будущие даты
        if ($date->isFuture()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Нельзя отметить привычку за будущие даты'],
            ], 422);
        }

        // Проверяем уже выполнено
        $exists = $habitModel->completions()
            ->whereDate('completed_date', $date)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Привычка уже отмечена за этот день'],
            ], 422);
        }

        DB::transaction(function () use ($habitModel, $date) {
            // Создаём запись
            $habitModel->completions()->create(['completed_date' => $date]);

            // Обновляем streak
            $this->updateStreak($habitModel);
        });

        $habitModel->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'current_streak' => $habitModel->current_streak,
                'best_streak' => $habitModel->best_streak,
                'last_completed_at' => $habitModel->last_completed_at,
            ],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * GET /api/v1/habits/{habit}/stats
     */
    public function stats(Request $request, int $habit): JsonResponse
    {
        $habitModel = Habit::findOrFail($habit);

        if ($habitModel->user_id !== $request->user()->id) {
            abort(403, 'Нет доступа');
        }

        $days = $request->integer('days', 30);
        $completionRate = $habitModel->getCompletionRate($days);

        $completionsByDay = $habitModel->completions()
            ->where('completed_date', '>=', now()->subDays($days))
            ->get()
            ->map(fn ($c) => $c->completed_date->format('Y-m-d'))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'current_streak' => $habitModel->current_streak,
                'best_streak' => $habitModel->best_streak,
                'completion_rate' => round($completionRate * 100, 2),
                'completions_last_30_days' => $completionsByDay,
                'total_completions' => $habitModel->completions()->count(),
            ],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * Обновить streak привычки.
     */
    private function updateStreak(Habit $habit): void
    {
        $today = today();
        $consecutiveDays = 0;
        $currentDate = $today;

        // Считаем последовательные дни назад
        while (true) {
            $exists = $habit->completions()
                ->whereDate('completed_date', $currentDate)
                ->exists();

            if (!$exists) {
                break;
            }

            $consecutiveDays++;
            $currentDate = $currentDate->copy()->subDay();
        }

        $habit->update([
            'current_streak' => $consecutiveDays,
            'best_streak' => max($habit->best_streak, $consecutiveDays),
            'last_completed_at' => $today,
        ]);
    }
}
