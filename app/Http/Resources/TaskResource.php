<?php

namespace App\Http\Resources;

use App\Models\Task;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * @mixin Task
 */
class TaskResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'parent_task_id' => $this->parent_task_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => optional($this->status)->value ?? 'todo',
            'priority' => optional($this->priority)->value ?? 'medium',
            'due_at' => $this->due_at?->toISOString(),
            'position' => $this->position,
            'assignee_id' => $this->assignee_id,
            'created_by' => $this->created_by,
            'is_recurring' => $this->is_recurring,
            'recurring_rule' => $this->recurring_rule,

            // Computed
            'comments_count' => $this->whenCounted('comments', 0),
            'attachments_count' => $this->whenCounted('attachments', 0),
            'subtasks_total' => $this->subtasks_count ?? $this->getTotalSubtasksCount(),
            'subtasks_completed' => $this->subtasks_completed_count ?? $this->getCompletedSubtasksCount(),

            // Relations
            'project' => $this->whenLoaded('project', fn () => new ProjectResource($this->project)),
            'assignee' => $this->whenLoaded('assignee', fn () => new UserResource($this->assignee)),
            'tags' => $this->whenLoaded('tags', fn () => TagResource::collection($this->tags)),
            'attachments' => $this->whenLoaded('attachments', fn () => AttachmentResource::collection($this->attachments)),
            'subtasks' => $this->whenLoaded('subtasks', fn () => TaskResource::collection($this->subtasks)),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
