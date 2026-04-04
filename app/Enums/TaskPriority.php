<?php

namespace App\Enums;

/**
 * Приоритеты задачи.
 * 
 * @see ТЗ №0 раздел 2.3
 */
enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

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
