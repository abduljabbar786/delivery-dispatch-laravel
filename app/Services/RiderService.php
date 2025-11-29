<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Events\RiderLocationUpdated;
use App\Helpers\GeolocationHelper;
use App\Models\Order;
use App\Models\Rider;
use App\Models\RiderLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RiderService
{
    public function ingestLocations(Rider $rider, array $points): array
    {
        $currentOrder = $rider->currentOrder;
        $latestPoint = end($points);

        DB::beginTransaction();

        try {
            $locations = [];
            foreach ($points as $point) {
                $locationData = [
                    'rider_id' => $rider->id,
                    'order_id' => $currentOrder?->id,
                    'lat' => $point['lat'],
                    'lng' => $point['lng'],
                    'speed' => $point['speed'] ?? null,
                    'accuracy' => $point['accuracy'] ?? null,
                    'battery' => $point['battery'] ?? null,
                    'recorded_at' => $point['ts'] ?? now(),
                    'pos' => DB::raw("ST_GeomFromText('POINT({$point['lng']} {$point['lat']})', 4326)"),
                ];

                $locations[] = $locationData;
            }

            foreach ($locations as $location) {
                RiderLocation::query()->create($location);
            }

            $this->updateRiderLatestPosition($rider, $latestPoint);

            if ($currentOrder && $currentOrder->status === OrderStatus::PICKED_UP->value) {
                $this->checkAndUpdateOrderStatus($currentOrder, $latestPoint);
            }

            DB::commit();

            $this->broadcastLocation($rider, $latestPoint);

            return [
                'message' => 'Locations ingested successfully',
                'count' => count($points),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function updateRiderLatestPosition(Rider $rider, array $latestPoint): void
    {
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
    }

    private function checkAndUpdateOrderStatus(Order $order, array $latestPoint): void
    {
        if (!$order->picked_up_at || now()->diffInSeconds($order->picked_up_at) < config('delivery.thresholds.auto_update_time', 30)) {
            return;
        }

        $pickupLat = config('delivery.pickup_location.lat');
        $pickupLng = config('delivery.pickup_location.lng');

        if (!$pickupLat || !$pickupLng) {
            return;
        }

        $distance = GeolocationHelper::calculateDistance(
            (float) $pickupLat,
            (float) $pickupLng,
            $latestPoint['lat'],
            $latestPoint['lng']
        );

        if ($distance > config('delivery.thresholds.delivery_start_distance', 100)) {
            $order->update(['status' => OrderStatus::OUT_FOR_DELIVERY]);
            broadcast(new OrderStatusChanged($order->fresh()))->toOthers();
        }
    }

    private function broadcastLocation(Rider $rider, array $latestPoint): void
    {
        Cache::forget('riders:positions:latest');

        $cacheKey = "rider_location_broadcast_{$rider->id}";
        $canBroadcast = !Cache::has($cacheKey);

        if ($canBroadcast) {
            Cache::put($cacheKey, true, 1);

            broadcast(new RiderLocationUpdated(
                $rider->id,
                $latestPoint['lat'],
                $latestPoint['lng'],
                $latestPoint['battery'] ?? $rider->battery,
                now()
            ))->toOthers();
        }
    }
}
