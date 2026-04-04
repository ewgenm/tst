<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Habit;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $tasksByStatus = Task::where(function ($q) use ($user) {
                $q->whereNull('project_id')
                    ->where(function ($sub) use ($user) {
                        $sub->where('created_by', $user->id)
                            ->orWhere('assignee_id', $user->id);
                    });
            })
            ->orWhereHas('project', function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('activeMembers', fn ($m) => $m->where('user_id', $user->id));
            })
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $overdueTasks = Task::where(function ($q) use ($user) {
                $q->whereNull('project_id')
                    ->where(function ($sub) use ($user) {
                        $sub->where('created_by', $user->id)
                            ->orWhere('assignee_id', $user->id);
                    });
            })
            ->orWhereHas('project', function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('activeMembers', fn ($m) => $m->where('user_id', $user->id));
            })
            ->where('due_at', '<', now())
            ->where('status', '!=', 'done')
            ->count();

        $habitsStreaks = Habit::where('user_id', $user->id)
            ->where('current_streak', '>', 0)
            ->orderBy('current_streak', 'desc')
            ->get()
            ->map(fn ($h) => [
                'name' => $h->name,
                'current_streak' => $h->current_streak,
            ]);

        $projectsCount = Project::where('owner_id', $user->id)
            ->orWhereHas('activeMembers', fn ($m) => $m->where('user_id', $user->id))
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'tasks_by_status' => $tasksByStatus,
                'overdue_tasks' => $overdueTasks,
                'habits_streaks' => $habitsStreaks,
                'projects_count' => $projectsCount,
            ],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }
}
