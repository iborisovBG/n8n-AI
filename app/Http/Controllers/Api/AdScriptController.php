<?php

namespace App\Http\Controllers\Api;

use App\Events\AdScriptTaskCreated;
use App\Http\Resources\AdScriptTaskResource;
use App\Jobs\SendAdScriptToN8n;
use App\Models\AdScriptTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdScriptController
{
    /**
     * Health check endpoint.
     */
    public function health(): JsonResponse
    {
        try {
            DB::connection()->getPdo();

            $statistics = [
                'pending' => AdScriptTask::where('status', 'pending')->count(),
                'completed' => AdScriptTask::where('status', 'completed')->count(),
                'failed' => AdScriptTask::where('status', 'failed')->count(),
                'total' => AdScriptTask::count(),
            ];

            return response()->json([
                'status' => 'healthy',
                'database' => 'connected',
                'statistics' => $statistics,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Health check failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'unhealthy',
                'database' => 'disconnected',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }

    /**
     * Store a newly created ad script task.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference_script' => ['required', 'string', 'min:10'],
            'outcome_description' => ['required', 'string', 'min:10'],
        ]);

        $task = AdScriptTask::create([
            'reference_script' => $validated['reference_script'],
            'outcome_description' => $validated['outcome_description'],
            'status' => 'pending',
        ]);

        // Dispatch job to send task to n8n
        SendAdScriptToN8n::dispatch($task);

        // Fire event
        event(new AdScriptTaskCreated($task));

        return response()->json([
            'data' => new AdScriptTaskResource($task),
        ], 201);
    }

    /**
     * Display a listing of ad script tasks.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AdScriptTask::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference_script', 'like', "%{$search}%")
                    ->orWhere('outcome_description', 'like', "%{$search}%")
                    ->orWhere('new_script', 'like', "%{$search}%")
                    ->orWhere('analysis', 'like', "%{$search}%");
            });
        }

        // Pagination with max limit
        $perPage = min($request->input('per_page', 15), 100);
        $tasks = $query->latest()->paginate($perPage);

        return AdScriptTaskResource::collection($tasks);
    }

    /**
     * Display the specified ad script task.
     */
    public function show(int $id): JsonResponse
    {
        $task = AdScriptTask::findOrFail($id);

        return response()->json([
            'task' => new AdScriptTaskResource($task),
        ]);
    }

    /**
     * Update task result from n8n callback.
     */
    public function updateResult(Request $request, int $id): JsonResponse
    {
        $task = AdScriptTask::findOrFail($id);

        // Prevent updating completed tasks
        if ($task->status === 'completed') {
            return response()->json([
                'message' => 'Task has already been completed',
            ], 409);
        }

        $validated = $request->validate([
            'new_script' => ['required', 'string', 'min:10'],
            'analysis' => ['required', 'string', 'min:10'],
        ]);

        $task->update([
            'new_script' => $validated['new_script'],
            'analysis' => $validated['analysis'],
            'status' => 'completed',
        ]);

        return response()->json([
            'message' => 'Task result updated successfully',
            'task' => new AdScriptTaskResource($task),
        ]);
    }
}
