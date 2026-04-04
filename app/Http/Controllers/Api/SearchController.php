<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Habit;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:200'],
            'types' => ['nullable', 'string'],
        ]);

        $query = $request->input('q');
        $types = $request->input('types') ? explode(',', $request->input('types')) : ['tasks', 'projects', 'habits'];
        $userId = $request->user()->id;

        $results = [];

        if (in_array('tasks', $types)) {
            $results['tasks'] = Task::where('title', 'like', "%{$query}%")
                ->where(function ($q) use ($userId) {
                    $q->whereNull('project_id')
                        ->where(function ($sub) use ($userId) {
                            $sub->where('created_by', $userId)
                                ->orWhere('assignee_id', $userId);
                        });
                })
                ->orWhereHas('project', function ($q) use ($userId) {
                    $q->where('owner_id', $userId)
                        ->orWhereHas('activeMembers', fn ($m) => $m->where('user_id', $userId));
                })
                ->limit(50)
                ->get(['id', 'title', 'status', 'project_id'])
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'type' => 'task',
                    'status' => $t->status,
                ]);
        }

        if (in_array('projects', $types)) {
            $results['projects'] = Project::where('name', 'like', "%{$query}%")
                ->where(function ($q) use ($userId) {
                    $q->where('owner_id', $userId)
                        ->orWhereHas('activeMembers', fn ($m) => $m->where('user_id', $userId));
                })
                ->limit(50)
                ->get(['id', 'name'])
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'type' => 'project',
                ]);
        }

        if (in_array('habits', $types)) {
            $results['habits'] = Habit::where('user_id', $userId)
                ->where('name', 'like', "%{$query}%")
                ->limit(50)
                ->get(['id', 'name'])
                ->map(fn ($h) => [
                    'id' => $h->id,
                    'name' => $h->name,
                    'type' => 'habit',
                ]);
        }

        return response()->json([
            'success' => true,
            'data' => $results,
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }
}
