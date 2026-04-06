<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'parent_task_id',
        'assignee_id',
        'created_by',
        'title',
        'description',
        'status',
        'priority',
        'due_at',
        'position',
        'is_recurring',
        'recurring_rule',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_at' => 'datetime',
            'position' => 'integer',
            'is_recurring' => 'boolean',
        ];
    }

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user assigned to the task.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Get the user who created the task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the parent task (for subtasks).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Get the subtasks.
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Get the comments.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at');
    }

    /**
     * Get the attachments.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get the tags.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'task_tag');
    }

    /**
     * Scope: only tasks in inbox (no project).
     */
    public function scopeInbox($query)
    {
        return $query->whereNull('project_id')->where('status', '!=', TaskStatus::Done->value);
    }

    /**
     * Scope: overdue tasks.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_at', '<', now())
            ->where('status', '!=', TaskStatus::Done->value);
    }

    /**
     * Check if task is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_at !== null
            && $this->due_at->isPast()
            && $this->status !== TaskStatus::Done;
    }

    /**
     * Check if task is a subtask.
     */
    public function isSubtask(): bool
    {
        return $this->parent_task_id !== null;
    }

    /**
     * Check if task is in Inbox.
     */
    public function isInbox(): bool
    {
        return $this->project_id === null;
    }

    /**
     * Get all subtasks recursively (including nested subtasks).
     */
    public function allSubtasks(): \Illuminate\Database\Eloquent\Collection
    {
        $subtasks = $this->subtasks()->with('allSubtasks')->get();
        $all = collect();

        foreach ($subtasks as $subtask) {
            $all->push($subtask);
            $all = $all->merge($subtask->allSubtasks());
        }

        return $all;
    }

    /**
     * Get total subtasks count recursively.
     */
    public function getTotalSubtasksCount(): int
    {
        return $this->allSubtasks()->count();
    }

    /**
     * Get completed subtasks count recursively.
     */
    public function getCompletedSubtasksCount(): int
    {
        return $this->allSubtasks()->where('status', TaskStatus::Done)->count();
    }
}
