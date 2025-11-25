<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::query()->with(['riders', 'orders']);

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return $query->orderBy('name')->get();
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
        $branch = Branch::create($validated);

        // Update spatial column if lat/lng provided
        if (isset($validated['lat']) && isset($validated['lng'])) {
            DB::statement(
                'UPDATE branches SET pickup_pos = ST_GeomFromText(?, 4326) WHERE id = ?',
                ["POINT({$validated['lng']} {$validated['lat']})", $branch->id]
            );
        }

        return response()->json([
            'data' => $branch->fresh()
        ], 201);
    }

    public function show(Branch $branch)
    {
        $branch->load(['riders', 'orders', 'settings']);

        return response()->json([
            'data' => $branch
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

        $branch->delete();

        return response()->json([
            'message' => 'Branch deleted successfully'
        ]);
    }

    public function activate(Branch $branch)
    {
        $branch->update(['is_active' => true]);

        return response()->json([
            'data' => $branch->fresh()
        ]);
    }

    public function deactivate(Branch $branch)
    {
        $branch->update(['is_active' => false]);

        return response()->json([
            'data' => $branch->fresh()
        ]);
    }
}
