<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\FlowStoreRequest;
use App\Http\Requests\Api\FlowUpdateRequest;
use App\Http\Resources\FlowResource;
use App\Models\Flow;
use App\Services\FlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlowController extends ApiController
{
    protected FlowService $flowService;

    public function __construct(FlowService $flowService)
    {
        $this->flowService = $flowService;
    }

    /**
     * Display a listing of the user's flows.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Flow::class);

        $query = Flow::where('user_id', auth()->id());

        // Pagination
        $perPage = min($request->query('per_page', 15), 100);
        $flows = $query->latest()->paginate($perPage);

        return $this->success([
            'flows' => FlowResource::collection($flows),
            'pagination' => [
                'total' => $flows->total(),
                'per_page' => $flows->perPage(),
                'current_page' => $flows->currentPage(),
                'last_page' => $flows->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created flow.
     */
    public function store(FlowStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Flow::class);

        try {
            $flow = $this->flowService->createFlow(
                $request->user(),
                $request->validated()
            );

            return $this->success(
                new FlowResource($flow),
                'Flow saved successfully',
                201
            );

        } catch (\InvalidArgumentException $e) {
            // Validation error (e.g., cycle detection)
            return $this->error($e->getMessage(), 422);

        } catch (\Exception $e) {
            return $this->error('Failed to create flow: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified flow.
     */
    public function show(string $id): JsonResponse
    {
        $flow = Flow::where('user_id', auth()->id())->find($id);

        if (!$flow) {
            return $this->notFound('Flow not found');
        }

        $this->authorize('view', $flow);

        return $this->success(new FlowResource($flow));
    }

    /**
     * Update the specified flow.
     */
    public function update(FlowUpdateRequest $request, string $id): JsonResponse
    {
        $flow = Flow::where('user_id', auth()->id())->find($id);

        if (!$flow) {
            return $this->notFound('Flow not found');
        }

        $this->authorize('update', $flow);

        try {
            $flow = $this->flowService->updateFlow($flow, $request->validated());

            return $this->success(
                new FlowResource($flow),
                'Flow updated successfully'
            );

        } catch (\InvalidArgumentException $e) {
            // Validation error (e.g., cycle detection)
            return $this->error($e->getMessage(), 422);

        } catch (\Exception $e) {
            return $this->error('Failed to update flow: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified flow.
     */
    public function destroy(string $id): JsonResponse
    {
        $flow = Flow::where('user_id', auth()->id())->find($id);

        if (!$flow) {
            return $this->notFound('Flow not found');
        }

        $this->authorize('delete', $flow);

        try {
            $this->flowService->deleteFlow($flow);

            return $this->success(null, 'Flow deleted successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to delete flow: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Toggle public status of the flow.
     */
    public function togglePublic(string $id): JsonResponse
    {
        $flow = Flow::where('user_id', auth()->id())->find($id);

        if (!$flow) {
            return $this->notFound('Flow not found');
        }

        $this->authorize('togglePublic', $flow);

        try {
            $flow->is_public = !$flow->is_public;
            
            // Generate slug if making public and no slug exists
            if ($flow->is_public && !$flow->public_slug) {
                $flow->public_slug = $flow->generateUniqueSlug();
            }
            
            $flow->save();

            return $this->success(
                new FlowResource($flow),
                $flow->is_public ? 'Flow is now public' : 'Flow is now private'
            );

        } catch (\Exception $e) {
            return $this->error('Failed to toggle public status: ' . $e->getMessage(), 500);
        }
    }
}
