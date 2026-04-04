<?php

namespace App\Http\Resources;

use App\Models\ProjectMember;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * @mixin ProjectMember
 */
class ProjectMemberResource extends BaseResource
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
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'role' => $this->role,
            'status' => $this->status,
            'invited_by' => $this->invited_by,
            'invited_at' => $this->invited_at?->toISOString(),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'invitedBy' => $this->whenLoaded('invitedBy', fn () => new UserResource($this->invitedBy)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
