<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class InviteMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Проверяется в контроллере через $this->authorize()
    }

    /**
     * Get the validation rules that apply to the request.
     * Согласно ТЗ №0 раздел 6.5
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', 'in:admin,member,viewer'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email обязателен',
            'email.email' => 'Неверный формат email',
            'email.exists' => 'Пользователь с таким email не найден',
            'role.required' => 'Роль обязательна',
            'role.in' => 'Роль должна быть admin, member или viewer',
        ];
    }
}
