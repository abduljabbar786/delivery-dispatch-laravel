<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderStatusChanged;
use App\Events\RiderLocationUpdated;
use App\Helpers\GeolocationHelper;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Rider;
use App\Models\RiderLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class RiderController extends Controller
{
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

        return response()->json(['data' => $rider]);
    }

    public function myOrder(Request $request)
    {
        $user = $request->user();

        $rider = $user->rider;

        if (!$rider) {
            return response()->json(['message' => 'Rider not found'], 404);
        }

        $order = $rider->currentOrder;

        if (!$order) {
            return response()->noContent();
        }

        return response()->json($order);
    }

    /**
     * @throws Throwable
     */
    public function ingestLocations(Request $request)
    {
        $validated = $request->validate([
            'points' => 'required|array|max:50',
            'points.*.lat' => 'required|numeric|between:-90,90',
            'points.*.lng' => 'required|numeric|between:-180,180',
            'points.*.ts' => 'nullable|date',
            'points.*.accuracy' => 'nullable|numeric',
            'points.*.speed' => 'nullable|numeric',
            'points.*.battery' => 'nullable|integer|between:0,100',
        ]);

        $user = $request->user();

        $rider = $user->rider;

        if (!$rider) {
            return response()->json(['message' => 'Rider not found'], 404);
        }

        $currentOrder = $rider->currentOrder;

        DB::beginTransaction();

        try {
            $locations = [];
            $latestPoint = end($validated['points']);

            foreach ($validated['points'] as $point) {
                $locationData = [
                    'rider_id' => $rider->id,
                    'order_id' => $currentOrder?->id,
                    'lat' => $point['lat'],
                    'lng' => $point['lng'],
                    'speed' => $point['speed'] ?? null,
                    'accuracy' => $point['accuracy'] ?? null,
                    'battery' => $point['battery'] ?? null,
                    'recorded_at' => $point['ts'] ?? now(),
                ];

                // Add spatial point
                $locationData['pos'] = DB::raw("ST_GeomFromText('POINT({$point['lng']} {$point['lat']})', 4326)");

                $locations[] = $locationData;
            }

            // TODO: Bulk insert locations
            foreach ($locations as $location) {
                RiderLocation::query()->create($location);
            }

            // Update rider's latest position and battery
            $updateData = [
                'latest_lat' => $latestPoint['lat'],
                'latest_lng' => $latestPoint['lng'],
                'last_seen_at' => now(),
                'latest_pos' => DB::raw("ST_GeomFromText('POINT({$latestPoint['lng']} {$latestPoint['lat']})', 4326)"),
            ];

            if (isset($latestPoint['battery'])) {
                $updateData['battery'] = $latestPoint['battery'];
            }

            $rider->update($updateData);

            // Auto-update order status from PICKED_UP to OUT_FOR_DELIVERY
            // when rider moves away from the pickup location
            if ($currentOrder && $currentOrder->status === 'PICKED_UP') {
                $this->checkAndUpdateOrderStatus($currentOrder, $latestPoint);
            }

            DB::commit();

            // Clear rider positions cache to reflect latest updates
            Cache::forget('riders:positions:latest');

            // Throttle broadcasts - only broadcast once per second per rider
            $cacheKey = "rider_location_broadcast_{$rider->id}";
            $canBroadcast = ! Cache::has($cacheKey);

            if ($canBroadcast) {
                Cache::put($cacheKey, true, 1); // 1 second

                broadcast(new RiderLocationUpdated(
                    $rider->id,
                    $latestPoint['lat'],
                    $latestPoint['lng'],
                    $latestPoint['battery'] ?? $rider->battery,
                    now()
                ))->toOthers();
            }

            return response()->json([
                'message' => 'Locations ingested successfully',
                'count' => count($validated['points']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if rider has moved away from pickup location and auto-update order status
     *
     * @param Order $order
     * @param array $latestPoint
     * @return void
     */
    private function checkAndUpdateOrderStatus(Order $order, array $latestPoint): void
    {
        // Check if 30 seconds have passed since PICKED_UP
        if (!$order->picked_up_at || now()->diffInSeconds($order->picked_up_at) < 30) {
            return;
        }

        // Get pickup location from environment
        $pickupLat = (float) env('PICKUP_LOCATION_LAT');
        $pickupLng = (float) env('PICKUP_LOCATION_LNG');

        // Calculate distance from pickup location
        $distance = GeolocationHelper::calculateDistance(
            $pickupLat,
            $pickupLng,
            $latestPoint['lat'],
            $latestPoint['lng']
        );

        // If rider is more than 100 meters away, update the status to OUT_FOR_DELIVERY
        if ($distance > 100) {
            $oldStatus = $order->status;

            $order->update(['status' => 'OUT_FOR_DELIVERY']);

            // Create event
            OrderEvent::query()->create([
                'order_id' => $order->id,
                'type' => 'status_changed',
                'meta' => [
                    'old_status' => $oldStatus,
                    'new_status' => 'OUT_FOR_DELIVERY',
                    'auto_updated' => true,
                    'distance_from_pickup' => round($distance, 2),
                ],
            ]);

            // Broadcast event
            broadcast(new OrderStatusChanged($order->fresh()))->toOthers();
        }
    }
}
