<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * @mixin Project
 */
class ProjectResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_favorite' => $this->is_favorite,
            'is_archived' => $this->is_archived,
            'sort_order' => $this->sort_order,
            'tasks_count' => $this->whenCounted('tasks', 0),
            'owner' => $this->whenLoaded('owner', fn () => new UserResource($this->owner)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
