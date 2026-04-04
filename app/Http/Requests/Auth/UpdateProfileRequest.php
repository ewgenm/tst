<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserTheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $this->user()->id],
            'avatar_url' => ['nullable', 'url'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'locale' => ['sometimes', 'string', 'max:10'],
            'theme' => ['sometimes', Rule::in(UserTheme::values())],
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
            'email.unique' => 'Пользователь с таким email уже существует',
            'avatar_url.url' => 'Неверный формат URL для аватара',
            'timezone.timezone' => 'Неверный часовой пояс',
        ];
    }
}
