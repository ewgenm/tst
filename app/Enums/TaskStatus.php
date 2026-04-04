<?php

namespace App\Enums;

/**
 * Статусы задачи.
 * 
 * @see ТЗ №0 раздел 2.3
 */
enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Done = 'done';

    /**
     * Получить все доступные значения.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
