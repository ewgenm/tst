<?php

namespace Tests\Feature;

use App\Enums\UserTheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Пользователь может зарегистрироваться.
     */
    public function test_user_can_register(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'timezone' => 'Europe/Moscow',
            'locale' => 'ru',
            'theme' => UserTheme::Dark->value,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'avatar_url',
                        'timezone',
                        'locale',
                        'theme',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                    ],
                    'message',
                ],
                'meta' => ['timestamp'],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'name' => 'Test User',
                        'email' => 'test@example.com',
                        'timezone' => 'Europe/Moscow',
                        'locale' => 'ru',
                        'theme' => 'dark',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        // Проверка отправки письма для подтверждения email
        Notification::assertSentTo(
            User::where('email', 'test@example.com')->first(),
            VerifyEmail::class
        );
    }

    /**
     * Регистрация с уже занятым email возвращает ошибку.
     */
    public function test_register_with_existing_email_returns_error(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Another User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Регистрация с коротким паролем возвращает ошибку.
     */
    public function test_register_with_short_password_returns_error(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Регистрация с несовпадающими паролями возвращает ошибку.
     */
    public function test_register_with_mismatched_passwords_returns_error(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Вход с корректными данными.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'test@example.com',
                    ],
                ],
            ]);
    }

    /**
     * Вход с неверным паролем возвращает ошибку.
     */
    public function test_login_with_invalid_password_returns_error(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Вход без подтверждения email возвращает ошибку.
     */
    public function test_login_without_email_verification_returns_error(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null, // Не подтверждён
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'email' => ['Пожалуйста, подтвердите ваш email адрес'],
                ],
            ]);
    }

    /**
     * Доступ к /me без аутентификации возвращает 401.
     */
    public function test_me_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Обновление профиля аутентифицированным пользователем.
     */
    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/me', [
                'name' => 'Updated Name',
                'timezone' => 'America/New_York',
                'theme' => UserTheme::Light->value,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Name',
                    'timezone' => 'America/New_York',
                    'theme' => 'light',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Обновление профиля с неверным timezone возвращает ошибку.
     */
    public function test_update_profile_with_invalid_timezone_returns_error(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/me', [
                'timezone' => 'Invalid/Timezone',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['timezone']);
    }

    /**
     * Обновление профиля с неверным theme возвращает ошибку.
     */
    public function test_update_profile_with_invalid_theme_returns_error(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/me', [
                'theme' => 'invalid_theme',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['theme']);
    }

    /**
     * Проверка формата ответа API (success, data, meta).
     */
    public function test_api_response_format(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        // Ошибка валидации тоже должна иметь правильный формат
        $response->assertJsonStructure([
            'message',
            'errors',
        ]);
    }
}
