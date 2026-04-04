<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Сервис для аутентификации и управления пользователями.
 * 
 * Бизнес-логика вынесена из контроллера согласно принципу Thin Controllers.
 */
class AuthService
{
    /**
     * Регистрация нового пользователя.
     *
     * @param array<string, mixed> $data
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'timezone' => $data['timezone'] ?? 'Europe/Moscow',
            'locale' => $data['locale'] ?? 'ru',
            'theme' => $data['theme'] ?? 'system',
            'email_verified_at' => isset($data['verified']) && filter_var($data['verified'], FILTER_VALIDATE_BOOLEAN) ? now() : null,
        ]);

        // Отправка письма для подтверждения email (только если не verified)
        if (!$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }

    /**
     * Аутентификация пользователя.
     *
     * @throws ValidationException
     */
    public function login(string $email, string $password): User
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль'],
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Пожалуйста, подтвердите ваш email адрес'],
            ]);
        }

        return $user;
    }
}
