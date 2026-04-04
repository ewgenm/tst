<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Rules\ValidRecurringRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'parent_task_id' => ['nullable', 'exists:tasks,id'],
            'status' => ['sometimes', Rule::in(TaskStatus::values())],
            'priority' => ['sometimes', Rule::in(TaskPriority::values())],
            'due_at' => ['nullable', 'date'],
            'position' => ['sometimes', 'integer'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'is_recurring' => ['sometimes', 'boolean'],
            'recurring_rule' => [
                'nullable',
                'string',
                new ValidRecurringRule(),
            ],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ];
    }
}
