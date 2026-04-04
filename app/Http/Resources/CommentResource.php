<?php

namespace App\Http\Resources;

use App\Models\Comment;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * @mixin Comment
 */
class CommentResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'user_id' => $this->user_id,
            'parent_comment_id' => $this->parent_comment_id,
            'content' => $this->content,
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'replies_count' => $this->whenCounted('replies', 0),
            'attachments' => $this->whenLoaded('attachments', fn () => AttachmentResource::collection($this->attachments)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
