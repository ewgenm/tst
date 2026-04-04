<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $query = Tag::query()->where('user_id', $user->id);

        if ($request->has('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        $tags = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => TagResource::collection($tags),
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'regex:/^#[0-9A-F]{6}$/i'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        // Check unique tag name within project
        $existing = Tag::where('user_id', $request->user()->id)
            ->where('name', $data['name'])
            ->where('project_id', $data['project_id'] ?? null)
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Тег с таким именем уже существует'],
            ], 422);
        }

        $tag = Tag::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'color' => $data['color'] ?? '#6B7280',
            'project_id' => $data['project_id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => new TagResource($tag),
            'meta' => ['timestamp' => now()->toISOString()],
        ], 201);
    }

    public function update(Request $request, int $tag): TagResource
    {
        $tagModel = Tag::findOrFail($tag);
        $this->authorize('update', $tagModel);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'color' => ['nullable', 'regex:/^#[0-9A-F]{6}$/i'],
        ]);

        $tagModel->update($data);

        return new TagResource($tagModel);
    }

    public function destroy(Request $request, int $tag): JsonResponse
    {
        $tagModel = Tag::findOrFail($tag);
        $this->authorize('delete', $tagModel);

        $tagModel->delete();

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Тег удалён'],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }
}
