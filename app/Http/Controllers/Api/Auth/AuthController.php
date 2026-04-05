<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    /**
     * Регистрация нового пользователя.
     * 
     * POST /api/v1/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'message' => 'Регистрация успешна. Пожалуйста, проверьте ваш email для подтверждения.',
            ],
            'meta' => ['timestamp' => now()->toISOString()],
        ], 201);
    }

    /**
     * Вход в систему.
     * 
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->login(
            $request->validated('email'),
            $request->validated('password')
        );

        // Создаём сессию для Sanctum SPA аутентификации
        \Illuminate\Support\Facades\Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'message' => 'Вход выполнен успешно',
            ],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * Выход из системы.
     * 
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Выход выполнен успешно'],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * Получить данные текущего пользователя.
     * 
     * GET /api/v1/auth/me
     */
    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['ownedProjects', 'createdTasks']);

        return new UserResource($user);
    }

    /**
     * Обновить профиль пользователя.
     * 
     * PUT /api/v1/auth/me
     */
    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();
        $user->update($request->validated());

        return new UserResource($user);
    }
}
