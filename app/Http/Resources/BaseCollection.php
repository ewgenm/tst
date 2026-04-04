<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Базовый класс для коллекций API ресурсов с пагинацией.
 * 
 * Формат ответа:
 * {
 *   "success": true,
 *   "data": [ ... ],
 *   "pagination": {
 *     "current_page": 1,
 *     "per_page": 20,
 *     "total": 150,
 *     "total_pages": 8,
 *     "has_more": true
 *   },
 *   "meta": { "timestamp": "2026-04-03T10:00:00Z" }
 * }
 */
abstract class BaseCollection extends ResourceCollection
{
    /**
     * Customize the outgoing response for the resource collection.
     */
    public function toResponse($request): array
    {
        return [
            'success' => true,
            'data' => $this->collection,
            'pagination' => [
                'current_page' => $this->currentPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
                'total_pages' => $this->lastPage(),
                'has_more' => $this->hasMorePages(),
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
            ],
        ];
    }
}
