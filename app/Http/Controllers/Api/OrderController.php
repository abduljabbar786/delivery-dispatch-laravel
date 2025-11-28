<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->with(['rider', 'branch']);

        // Filter by branch if provided
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|unique:orders,code',
            'branch_id' => 'required|exists:branches,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
        ]);

        // Create a spatial point if lat/lng provided
        if (isset($validated['lat']) && isset($validated['lng'])) {
            $validated['dest_pos'] = DB::raw("ST_GeomFromText('POINT({$validated['lng']} {$validated['lat']})', 4326)");
        }

        $order = Order::query()->create($validated);

        return response()->json($order->fresh(), 201);
    }

    /**
     * @throws Throwable
     */
    public function assign(Request $request, Order $order)
    {
        $validated = $request->validate([
            'rider_id' => 'required|exists:riders,id',
        ]);

        return DB::transaction(function () use ($order, $validated) {
            $rider = Rider::query()->lockForUpdate()->findOrFail($validated['rider_id']);

            // Check if rider belongs to the same branch as the order
            if ($order->branch_id && $rider->branch_id && $order->branch_id !== $rider->branch_id) {
                return response()->json([
                    'message' => 'Rider must belong to the same branch as the order',
                ], 422);
            }

            // Check if rider already has an active order
            $activeOrder = Order::query()->where('assigned_rider_id', $rider->id)
                ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'OUT_FOR_DELIVERY'])
                ->exists();

            if ($activeOrder) {
                return response()->json([
                    'message' => 'Rider already has an active order',
                ], 422);
            }

            // Assign order
            $order->update([
                'status' => 'ASSIGNED',
                'assigned_rider_id' => $rider->id,
            ]);

            // Update rider status
            $rider->update(['status' => 'BUSY']);

            // Broadcast event
            broadcast(new OrderStatusChanged($order->fresh()))->toOthers();

            return response()->json($order->fresh()->load('rider'));
        });
    }

    /**
     * @throws Throwable
     */
    public function reassign(Request $request, Order $order)
    {
        $validated = $request->validate([
            'rider_id' => 'required|exists:riders,id',
        ]);

        return DB::transaction(function () use ($order, $validated) {
            $oldRider = $order->assigned_rider_id ? Rider::query()->find($order->assigned_rider_id) : null;
            $newRider = Rider::query()->lockForUpdate()->findOrFail($validated['rider_id']);

            // Check if rider belongs to the same branch as the order
            if ($order->branch_id && $newRider->branch_id && $order->branch_id !== $newRider->branch_id) {
                return response()->json([
                    'message' => 'Rider must belong to the same branch as the order',
                ], 422);
            }

            // Check if the new rider already has an active order
            $activeOrder = Order::query()->where('assigned_rider_id', $newRider->id)
                ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'OUT_FOR_DELIVERY'])
                ->exists();

            if ($activeOrder) {
                return response()->json([
                    'message' => 'New rider already has an active order',
                ], 422);
            }

            // Reassign order
            $order->update([
                'status' => 'ASSIGNED',
                'assigned_rider_id' => $newRider->id,
            ]);

            // Update the old rider status to IDLE if they have no other active orders
            if ($oldRider) {
                $oldRiderHasOtherOrders = Order::query()->where('assigned_rider_id', $oldRider->id)
                    ->where('id', '!=', $order->id)
                    ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'OUT_FOR_DELIVERY'])
                    ->exists();

                if (!$oldRiderHasOtherOrders) {
                    $oldRider->update(['status' => 'IDLE']);
                }
            }

            // Update new rider status
            $newRider->update(['status' => 'BUSY']);

            // Broadcast event
            broadcast(new OrderStatusChanged($order->fresh()))->toOthers();

            return response()->json($order->fresh()->load('rider'));
        });
    }

    /**
     * @throws Throwable
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['PICKED_UP', 'OUT_FOR_DELIVERY', 'DELIVERED', 'FAILED'])],
            'reason' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($order, $validated) {
            $newStatus = $validated['status'];

            // Update order status
            $updateData = ['status' => $newStatus];

            // Set picked_up_at timestamp when status changes to PICKED_UP
            if ($newStatus === 'PICKED_UP') {
                $updateData['picked_up_at'] = now();
            }

            $order->update($updateData);

            // If the order is completed (DELIVERED or FAILED), set rider to IDLE
            if (in_array($newStatus, ['DELIVERED', 'FAILED']) && $order->assigned_rider_id) {
                $rider = Rider::query()->find($order->assigned_rider_id);
                if ($rider) {
                    // Check if rider has other active orders
                    $hasOtherOrders = Order::query()->where('assigned_rider_id', $rider->id)
                        ->where('id', '!=', $order->id)
                        ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'OUT_FOR_DELIVERY'])
                        ->exists();

                    if (!$hasOtherOrders) {
                        $rider->update(['status' => 'IDLE']);
                    }
                }
            }

            // Broadcast event
            broadcast(new OrderStatusChanged($order->fresh()))->toOthers();

            return response()->json($order->fresh()->load('rider'));
        });
    }
}
