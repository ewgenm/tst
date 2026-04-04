<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Базовый класс для всех API ресурсов.
 * 
 * Обеспечивает единый формат ответов:
 * {
 *   "success": true,
 *   "data": { ... },
 *   "meta": { "timestamp": "2026-04-03T10:00:00Z" }
 * }
 */
abstract class BaseResource extends JsonResource
{
    /**
     * Customize the outgoing response for the resource.
     */
    public function toResponse($request): array
    {
        return [
            'success' => true,
            'data' => $this->toArray($request),
            'meta' => [
                'timestamp' => now()->toISOString(),
            ],
        ];
    }
}
