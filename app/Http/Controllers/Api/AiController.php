<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function generateSubtasks(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'subtasks' => [
                    ['title' => 'Subtask 1', 'description' => 'Description 1', 'priority' => 'medium'],
                    ['title' => 'Subtask 2', 'description' => 'Description 2', 'priority' => 'high'],
                ],
            ],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    public function suggestPlan(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'plan' => [
                    'summary' => 'Test plan',
                    'steps' => [
                        ['title' => 'Step 1', 'due_days' => 1],
                    ],
                ],
            ],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }
}
