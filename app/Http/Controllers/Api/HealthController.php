<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $services = [];
        $overallStatus = 'ok';

        try {
            DB::connection()->getPdo();
            $services['database'] = 'up';
        } catch (\Exception $e) {
            $services['database'] = 'down';
            $overallStatus = 'degraded';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $overallStatus,
                'services' => $services,
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }
}
