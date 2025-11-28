<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : 'all';
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $cacheKey = "branches:list:active:{$isActive}:page:{$page}:per_page:{$perPage}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($request, $isActive, $perPage) {
            $query = Branch::query()
                ->withCount(['riders', 'orders'])
                ->with(['riders' => function ($query) {
                    $query->select('id', 'name', 'branch_id', 'status')->limit(5);
                }]);

            // Filter by active status
            if ($isActive !== 'all') {
                $query->where('is_active', $isActive);
            }

            return $query->orderBy('name')->paginate($perPage);
        });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        // Create the branch
        $branch = Branch::query()->create($validated);

        // Update spatial column if lat/lng provided
        if (isset($validated['lat']) && isset($validated['lng'])) {
            DB::statement(
                'UPDATE branches SET pickup_pos = ST_GeomFromText(?, 4326) WHERE id = ?',
                ["POINT({$validated['lng']} {$validated['lat']})", $branch->id]
            );
        }

        // Clear branch caches
        $this->clearBranchCache();

        return response()->json([
            'data' => $branch->fresh()
        ], 201);
    }

    public function show(Branch $branch)
    {
        $cacheKey = "branch:{$branch->id}:details";

        $branchData = Cache::remember($cacheKey, now()->addHour(), function () use ($branch) {
            return $branch->load(['riders', 'orders', 'settings']);
        });

        return response()->json([
            'data' => $branchData
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('branches', 'code')->ignore($branch->id)],
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'lat' => 'sometimes|numeric|between:-90,90',
            'lng' => 'sometimes|numeric|between:-180,180',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        $branch->update($validated);

        // Update spatial column if lat/lng changed
        if (isset($validated['lat']) && isset($validated['lng'])) {
            DB::statement(
                'UPDATE branches SET pickup_pos = ST_GeomFromText(?, 4326) WHERE id = ?',
                ["POINT({$validated['lng']} {$validated['lat']})", $branch->id]
            );
        }

        // Clear branch caches
        $this->clearBranchCache($branch->id);

        return response()->json([
            'data' => $branch->fresh()
        ]);
    }

    public function destroy(Branch $branch)
    {
        // Check if branch has any riders or orders
        $ridersCount = $branch->riders()->count();
        $ordersCount = $branch->orders()->count();

        if ($ridersCount > 0 || $ordersCount > 0) {
            return response()->json([
                'message' => 'Cannot delete branch with associated riders or orders. Please deactivate instead.',
                'riders_count' => $ridersCount,
                'orders_count' => $ordersCount,
            ], 422);
        }

        $branchId = $branch->id;
        $branch->delete();

        // Clear branch caches
        $this->clearBranchCache($branchId);

        return response()->json([
            'message' => 'Branch deleted successfully'
        ]);
    }

    public function activate(Branch $branch)
    {
        $branch->update(['is_active' => true]);

        // Clear branch caches
        $this->clearBranchCache($branch->id);

        return response()->json([
            'data' => $branch->fresh()
        ]);
    }

    public function deactivate(Branch $branch)
    {
        $branch->update(['is_active' => false]);

        // Clear branch caches
        $this->clearBranchCache($branch->id);

        return response()->json([
            'data' => $branch->fresh()
        ]);
    }

    /**
     * Clear all branch-related caches
     */
    protected function clearBranchCache(?int $branchId = null)
    {
        // Clear list caches
        Cache::forget('branches:list:active:all:page:1:per_page:20');
        Cache::forget('branches:list:active:1:page:1:per_page:20');
        Cache::forget('branches:list:active:0:page:1:per_page:20');

        // Clear specific branch cache if provided
        if ($branchId) {
            Cache::forget("branch:{$branchId}:details");
        }

        // Clear all paginated caches (this is a simple approach, could be improved with cache tags)
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget("branches:list:active:all:page:{$page}:per_page:20");
            Cache::forget("branches:list:active:1:page:{$page}:per_page:20");
            Cache::forget("branches:list:active:0:page:{$page}:per_page:20");
        }
    }
}
