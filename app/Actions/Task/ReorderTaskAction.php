<?php

namespace App\Actions\Task;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Изменение позиции задачи (сортировка).
 * 
 * Поддерживает:
 * - Прямую позицию: { "position": 5 }
 * - Относительную позицию: { "after_id": 123 }
 * 
 * @see ТЗ №0 раздел 4.4
 */
class ReorderTaskAction
{
    /**
     * @param Task $task
     * @param int|null $position Прямая позиция
     * @param int|null $afterId ID задачи, после которой вставить
     * @return Task
     */
    public function execute(Task $task, ?int $position = null, ?int $afterId = null): Task
    {
        return DB::transaction(function () use ($task, $position, $afterId) {
            $projectId = $task->project_id;
            $status = $task->status->value;

            if ($afterId !== null) {
                // Вставить после другой задачи
                $afterTask = Task::findOrFail($afterId);

                // Если сменился проект или статус — ставим в конец нового списка
                if ($afterTask->project_id !== $task->project_id
                    || $afterTask->status->value !== $task->status->value) {
                    $position = $this->getMaxPosition($projectId, $status) + 1;
                } else {
                    $position = $afterTask->position + 1;
                }
            }

            if ($position === null) {
                $position = $this->getMaxPosition($projectId, $status) + 1;
            }

            // Сдвигаем все задачи с позицией >= новой
            Task::where('project_id', $projectId)
                ->where('status', $status)
                ->where('id', '!=', $task->id)
                ->where('position', '>=', $position)
                ->increment('position');

            $task->update(['position' => $position]);

            return $task;
        });
    }

    /**
     * Получить максимальную позицию в контексте.
     */
    private function getMaxPosition(?int $projectId, string $status): int
    {
        return Task::where('project_id', $projectId)
            ->where('status', $status)
            ->max('position') ?? 0;
    }
}
