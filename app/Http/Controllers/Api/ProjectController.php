<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\InviteMemberRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\BaseCollection;
use App\Http\Resources\ProjectMemberResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Actions\Project\AcceptInviteAction;
use App\Actions\Project\InviteMemberAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProjectController extends Controller
{
    public function __construct(
        protected InviteMemberAction $inviteMemberAction,
        protected AcceptInviteAction $acceptInviteAction
    ) {
    }

    /**
     * Получить список проектов пользователя.
     *
     * GET /api/v1/projects?archived=false&sort=sort_order
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $query = Project::query()
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('activeMembers', function ($mq) use ($user) {
                        $mq->where('user_id', $user->id);
                    });
            })
            ->withCount('tasks')
            ->when($request->boolean('archived'), function ($q) {
                $q->withTrashed();
            });

        $paginated = QueryBuilder::for($query)
            ->allowedSorts('sort_order', 'name', 'created_at')
            ->defaultSort('sort_order')
            ->allowedFilters(AllowedFilter::exact('archived', 'is_archived'))
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'total_pages' => $paginated->lastPage(),
                'has_more' => $paginated->hasMorePages(),
            ],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * Создать новый проект.
     *
     * POST /api/v1/projects
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $project = Project::create([
            ...$request->validated(),
            'owner_id' => $user->id,
        ]);

        // Создаём запись владельца в project_members с ролью admin
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'data' => new ProjectResource($project),
            'meta' => ['timestamp' => now()->toISOString()],
        ], 201);
    }

    /**
     * Получить детали проекта.
     *
     * GET /api/v1/projects/{project}
     */
    public function show(Request $request, int $project): ProjectResource
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $projectModel = Project::findOrFail($project);
        
        if ($projectModel->owner_id !== $user->id && !$projectModel->isMember($user)) {
            abort(403, 'Доступ запрещён');
        }

        $projectModel->loadCount('tasks');
        $projectModel->loadMissing(['owner']);

        return new ProjectResource($projectModel);
    }

    /**
     * Обновить проект.
     *
     * PUT /api/v1/projects/{project}
     */
    public function update(UpdateProjectRequest $request, int $project): ProjectResource
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $projectModel = Project::findOrFail($project);

        if ($projectModel->owner_id !== $user->id && !$projectModel->isAdmin($user)) {
            abort(403, 'Доступ запрещён');
        }

        $projectModel->update($request->validated());

        return new ProjectResource($projectModel);
    }

    /**
     * Удалить проект (soft delete).
     * ТОЛЬКО владелец может удалить.
     *
     * DELETE /api/v1/projects/{project}
     */
    public function destroy(Request $request, int $project): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $projectModel = Project::findOrFail($project);

        if ($projectModel->owner_id !== $user->id) {
            abort(403, 'Только владелец может удалить проект');
        }

        $projectModel->delete();

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Проект удалён'],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * Восстановить удалённый проект.
     *
     * POST /api/v1/projects/{project}/restore
     */
    public function restore(Request $request, int $project): ProjectResource
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $projectModel = Project::withTrashed()->findOrFail($project);

        if ($projectModel->owner_id !== $user->id) {
            abort(403, 'Только владелец может восстановить проект');
        }

        $projectModel->restore();

        return new ProjectResource($projectModel);
    }

    /**
     * Архивировать проект.
     *
     * POST /api/v1/projects/{project}/archive
     */
    public function archive(Request $request, int $project): ProjectResource
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $projectModel = Project::findOrFail($project);

        if ($projectModel->owner_id !== $user->id && !$projectModel->isAdmin($user)) {
            abort(403, 'Доступ запрещён');
        }

        $projectModel->update(['is_archived' => true]);

        return new ProjectResource($projectModel);
    }

    /**
     * Выйти из проекта.
     *
     * DELETE /api/v1/projects/{project}/leave
     */
    public function leave(Request $request, int $project): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $projectModel = Project::findOrFail($project);

        // Владелец не может покинуть проект
        if ($projectModel->owner_id === $user->id) {
            abort(403, 'Владелец не может покинуть проект');
        }

        if (!$projectModel->isMember($user)) {
            abort(403, 'Вы не являетесь участником проекта');
        }

        $projectModel->members()->where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Вы покинули проект'],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * Пригласить участника.
     *
     * POST /api/v1/projects/{project}/invite
     */
    public function invite(InviteMemberRequest $request, int $project): JsonResponse
    {
        /** @var \App\Models\User $invitedBy */
        $invitedBy = $request->user();
        $projectModel = Project::findOrFail($project);

        if ($projectModel->owner_id !== $invitedBy->id && !$projectModel->isAdmin($invitedBy)) {
            abort(403, 'Только владелец или администратор может приглашать');
        }

        $userToAdd = User::where('email', $request->validated('email'))->firstOrFail();

        $membership = $this->inviteMemberAction->execute(
            $projectModel,
            $userToAdd,
            $request->validated('role'),
            $invitedBy
        );

        return response()->json([
            'success' => true,
            'data' => new ProjectMemberResource($membership),
            'meta' => ['timestamp' => now()->toISOString()],
        ], 201);
    }

    /**
     * Список участников проекта.
     *
     * GET /api/v1/projects/{project}/members
     */
    public function members(Request $request, int $project): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $projectModel = Project::findOrFail($project);

        if ($projectModel->owner_id !== $user->id && !$projectModel->isMember($user)) {
            abort(403, 'Доступ запрещён');
        }

        $members = $projectModel->members()->with(['user', 'invitedBy'])->get();

        return response()->json([
            'success' => true,
            'data' => ProjectMemberResource::collection($members),
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    /**
     * Принять приглашение.
     *
     * PATCH /api/v1/project-members/{membership}/accept
     */
    public function acceptInvite(Request $request, int $membership): ProjectMemberResource
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $membershipModel = ProjectMember::findOrFail($membership);

        if ($membershipModel->user_id !== $user->id) {
            abort(403, 'Вы не можете принять это приглашение');
        }

        $this->acceptInviteAction->execute($membershipModel, $user);

        return new ProjectMemberResource($membershipModel);
    }

    /**
     * Удалить участника (или отклонить приглашение).
     *
     * DELETE /api/v1/project-members/{membership}
     */
    public function removeMember(Request $request, int $membership): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $membershipModel = ProjectMember::findOrFail($membership);
        $projectModel = $membershipModel->project;

        if ($projectModel->owner_id !== $user->id && !$projectModel->isAdmin($user)) {
            abort(403, 'Только владелец или администратор может удалять участников');
        }

        $membershipModel->delete();

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Участник удалён'],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }
}
