<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\RiderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\Rider;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function create(array $data): Order
    {
        if (isset($data['lat']) && isset($data['lng'])) {
            $data['dest_pos'] = DB::raw("ST_GeomFromText('POINT({$data['lng']} {$data['lat']})', 4326)");
        }

        return Order::query()->create($data);
    }

    public function assign(Order $order, int $riderId): Order
    {
        return DB::transaction(function () use ($order, $riderId) {
            $rider = Rider::query()->lockForUpdate()->findOrFail($riderId);

            $this->validateAssignment($order, $rider);

            $order->update([
                'status' => OrderStatus::ASSIGNED,
                'assigned_rider_id' => $rider->id,
            ]);

            $rider->update(['status' => RiderStatus::BUSY]);

            broadcast(new OrderStatusChanged($order->fresh()))->toOthers();

            return $order->fresh()->load('rider');
        });
    }

    public function reassign(Order $order, int $riderId): Order
    {
        return DB::transaction(function () use ($order, $riderId) {
            $oldRider = $order->assigned_rider_id ? Rider::query()->find($order->assigned_rider_id) : null;
            $newRider = Rider::query()->lockForUpdate()->findOrFail($riderId);

            $this->validateAssignment($order, $newRider);

            $order->update([
                'status' => OrderStatus::ASSIGNED,
                'assigned_rider_id' => $newRider->id,
            ]);

            if ($oldRider) {
                $this->checkAndSetRiderIdle($oldRider, $order->id);
            }

            $newRider->update(['status' => RiderStatus::BUSY]);

            broadcast(new OrderStatusChanged($order->fresh()))->toOthers();

            return $order->fresh()->load('rider');
        });
    }

    public function updateStatus(Order $order, OrderStatus $status, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $status, $reason) {
            $updateData = ['status' => $status];

            if ($status === OrderStatus::PICKED_UP) {
                $updateData['picked_up_at'] = now();
            }

            $order->update($updateData);

            if (in_array($status, [OrderStatus::DELIVERED, OrderStatus::FAILED]) && $order->assigned_rider_id) {
                $rider = Rider::query()->find($order->assigned_rider_id);
                if ($rider) {
                    $this->checkAndSetRiderIdle($rider, $order->id);
                }
            }

            broadcast(new OrderStatusChanged($order->fresh()))->toOthers();

            return $order->fresh()->load('rider');
        });
    }

    private function validateAssignment(Order $order, Rider $rider): void
    {
        if ($order->branch_id && $rider->branch_id && $order->branch_id !== $rider->branch_id) {
            throw ValidationException::withMessages([
                'rider_id' => 'Rider must belong to the same branch as the order',
            ]);
        }

        $activeOrder = Order::query()->where('assigned_rider_id', $rider->id)
            ->whereIn('status', [OrderStatus::ASSIGNED, OrderStatus::PICKED_UP, OrderStatus::OUT_FOR_DELIVERY])
            ->exists();

        if ($activeOrder) {
            throw ValidationException::withMessages([
                'rider_id' => 'Rider already has an active order',
            ]);
        }
    }

    private function checkAndSetRiderIdle(Rider $rider, int $excludeOrderId): void
    {
        $hasOtherOrders = Order::query()->where('assigned_rider_id', $rider->id)
            ->where('id', '!=', $excludeOrderId)
            ->whereIn('status', [OrderStatus::ASSIGNED, OrderStatus::PICKED_UP, OrderStatus::OUT_FOR_DELIVERY])
            ->exists();

        if (!$hasOtherOrders) {
            $rider->update(['status' => RiderStatus::IDLE]);
        }
    }
}
