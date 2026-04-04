<?php

namespace App\Actions\Task;

use App\Enums\TaskStatus;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Завершение задачи.
 * 
 * Если задача recurring — создаёт копию с новым due_at.
 * 
 * @see ТЗ №0 раздел 4.2
 */
class CompleteTaskAction
{
    /**
     * @throws \Exception если задача уже завершена
     */
    public function execute(Task $task): Task
    {
        if ($task->status === TaskStatus::Done) {
            throw new \Exception('Задача уже завершена');
        }

        return DB::transaction(function () use ($task) {
            // Помечаем задачу как выполненную
            $task->update(['status' => TaskStatus::Done]);

            // Если recurring — создаём копию
            if ($task->is_recurring && $task->recurring_rule) {
                $this->createNextOccurrence($task);
            }

            return $task;
        });
    }

    /**
     * Создать следующую копию recurring задачи.
     */
    private function createNextOccurrence(Task $task): void
    {
        $nextDue = $this->calculateNextDue($task->due_at, $task->recurring_rule);

        $task->replicate()->fill([
            'status' => TaskStatus::Todo,
            'due_at' => $nextDue,
            'position' => 0, // Will be set to end of list
        ])->save();
    }

    /**
     * Вычислить следующий due_at по правилу RRULE.
     */
    private function calculateNextDue(?Carbon $currentDue, string $rule): Carbon
    {
        // Парсим FREQ и INTERVAL
        preg_match('/FREQ=(\w+)/', $rule, $freqMatch);
        preg_match('/INTERVAL=(\d+)/', $rule, $intervalMatch);

        $freq = $freqMatch[1] ?? 'DAILY';
        $interval = (int) ($intervalMatch[1] ?? 1);

        $due = $currentDue ?? now();

        return match ($freq) {
            'DAILY' => $due->copy()->addDays($interval),
            'WEEKLY' => $due->copy()->addWeeks($interval),
            'MONTHLY' => $due->copy()->addMonths($interval),
            'YEARLY' => $due->copy()->addYears($interval),
            default => $due->copy()->addDay(),
        };
    }
}
