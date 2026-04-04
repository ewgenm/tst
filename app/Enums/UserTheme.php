<?php

namespace App\Enums;

/**
 * Тема оформления пользователя.
 * 
 * Используется в поле users.theme
 */
enum UserTheme: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';

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
