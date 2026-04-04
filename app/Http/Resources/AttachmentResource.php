<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AttachmentResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attachable_type' => $this->attachable_type,
            'attachable_id' => $this->attachable_id,
            'user_id' => $this->user_id,
            'filename' => $this->filename,
            'url' => $this->url,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
