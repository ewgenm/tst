<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Атрибуты которые должны быть всегда загружены.
     */
    protected $with = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'color',
        'icon',
        'is_archived',
        'is_favorite',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
            'is_favorite' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Owner of the project.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Project members.
     */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * Active project members.
     */
    public function activeMembers(): HasMany
    {
        return $this->hasMany(ProjectMember::class)->where('status', 'active');
    }

    /**
     * Pending invites.
     */
    public function invites(): HasMany
    {
        return $this->hasMany(ProjectMember::class)->where('status', 'pending');
    }

    /**
     * Tasks in this project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Inbox tasks (no project).
     * This is a convenience method for the owner.
     */
    public function scopeWithInboxTasks($query, User $user)
    {
        return $query->whereHas('tasks', function ($q) {
            $q->inbox();
        });
    }

    /**
     * Tags scoped to this project.
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Check if user is the owner.
     */
    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    /**
     * Check if user is an admin member.
     * Owner always has admin rights.
     */
    public function isAdmin(User $user): bool
    {
        // Owner always has admin rights
        if ($this->owner_id === $user->id) {
            return true;
        }

        return $this->members()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Check if user is a member (any role).
     * Owner is always considered a member.
     */
    public function isMember(User $user): bool
    {
        // Owner is always a member
        if ($this->owner_id === $user->id) {
            return true;
        }

        return $this->members()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get user's membership.
     */
    public function getMembership(User $user): ?ProjectMember
    {
        return $this->members()->where('user_id', $user->id)->first();
    }
}
