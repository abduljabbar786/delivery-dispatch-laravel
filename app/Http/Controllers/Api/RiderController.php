<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rider\IngestLocationRequest;
use App\Models\Order;
use App\Models\Rider;
use App\Services\RiderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiderController extends Controller
{
    public function __construct(
        protected RiderService $riderService
    ) {
    }

    public function index(Request $request)
    {
        $query = Rider::query()->with('branch');

        // Filter by branch if provided
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return $query->orderBy('name')->paginate(20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|unique:riders,phone',
            'branch_id' => 'required|exists:branches,id',
            'latest_lat' => 'nullable|numeric|between:-90,90',
            'latest_lng' => 'nullable|numeric|between:-180,180',
        ]);

        // Set default status to IDLE for new riders
        $validated['status'] = 'IDLE';

        // If location is provided, set the spatial point for latest_pos
        if (isset($validated['latest_lat']) && isset($validated['latest_lng'])) {
            $validated['latest_pos'] = DB::raw("ST_GeomFromText('POINT({$validated['latest_lng']} {$validated['latest_lat']})', 4326)");
        }

        $rider = Rider::query()->create($validated);

        return response()->json([
            'data' => $rider->load('branch')
        ], 201);
    }

    public function show(Rider $rider)
    {
        return response()->json([
            'data' => $rider
        ]);
    }

    public function update(Request $request, Rider $rider)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'status' => 'sometimes|in:IDLE,BUSY,OFFLINE',
        ]);

        $rider->update($validated);

        return response()->json([
            'data' => $rider->fresh()
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $rider = $user->rider;

        if (!$rider) {
            return response()->json(['message' => 'Rider not found'], 404);
        }

        // Load branch relationship
        $rider->load('branch');

        return response()->json(['data' => $rider]);
    }

    public function myOrder(Request $request)
    {
        $user = $request->user();

        $rider = $user->rider;

        if (!$rider) {
            return response()->json(['message' => 'Rider not found'], 404);
        }

        // Fetch current order directly with a query
        $order = Order::where('assigned_rider_id', $rider->id)
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'OUT_FOR_DELIVERY'])
            ->with(['branch'])
            ->latest('updated_at')
            ->first();

        if (!$order) {
            return response()->noContent();
        }

        return response()->json($order);
    }

    public function ingestLocations(IngestLocationRequest $request)
    {
        $user = $request->user();
        $rider = $user->rider;

        if (!$rider) {
            return response()->json(['message' => 'Rider not found'], 404);
        }

        $result = $this->riderService->ingestLocations($rider, $request->points);

        return response()->json($result);
    }
}
