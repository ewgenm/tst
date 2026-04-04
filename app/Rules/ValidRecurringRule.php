<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Валидация формата recurring_rule.
 * 
 * Допустимые форматы:
 * - FREQ=DAILY
 * - FREQ=WEEKLY
 * - FREQ=MONTHLY
 * - FREQ=YEARLY
 * - FREQ=WEEKLY;INTERVAL=2
 * - FREQ=WEEKLY;BYDAY=MO,WE,FR
 * 
 * @see ТЗ №1 раздел 5.3
 */
class ValidRecurringRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value && !preg_match('/^FREQ=(DAILY|WEEKLY|MONTHLY|YEARLY)(;INTERVAL=\d+)?(;BYDAY=[A-Z,]+)?$/', $value)) {
            $fail('Неверный формат правила повторения. Допустимы: FREQ=DAILY, FREQ=WEEKLY, FREQ=MONTHLY, FREQ=YEARLY');
        }
    }
}
