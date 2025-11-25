<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class PosWebhookController extends Controller
{
    /**
     * Receive an order from a POS system and create it in a dispatch system
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function createOrder(Request $request)
    {
        try {
            // Validate incoming webhook data
            $validated = $request->validate([
                'pos_order_id' => 'required|string|max:255',
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'delivery_address' => 'required|string',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'notes' => 'nullable|string',
                'items' => 'nullable|array',
                'total_amount' => 'nullable|numeric',
            ]);

            // Check if the order already exists (prevent duplicates)
            $existingOrder = Order::query()->where('code', $validated['pos_order_id'])->first();
            if ($existingOrder) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order already exists in dispatch system',
                    'order_id' => $existingOrder->id,
                    'order_code' => $existingOrder->code,
                ], 200);
            }

            DB::beginTransaction();

            // Prepare order data
            $orderData = [
                'code' => $validated['pos_order_id'],
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'address' => $validated['delivery_address'],
                'status' => 'UNASSIGNED',
            ];

            // Handle coordinates
            if (isset($validated['latitude']) && isset($validated['longitude'])) {
                $orderData['lat'] = $validated['latitude'];
                $orderData['lng'] = $validated['longitude'];
                $orderData['dest_pos'] = DB::raw("ST_GeomFromText('POINT({$validated['longitude']} {$validated['latitude']})', 4326)");
            } else {
                // If no coordinates provided, use default/placeholder
                // In production, you'd want to geocode the address here
                Log::warning("Order {$validated['pos_order_id']} created without coordinates");
                $orderData['lat'] = 40.7128; // Default to NYC center
                $orderData['lng'] = -74.0060;
                $orderData['dest_pos'] = DB::raw("ST_GeomFromText('POINT(-74.0060 40.7128)', 4326)");
            }

            // Add notes (can include order items and total)
            $notes = $validated['notes'] ?? '';
            if (isset($validated['items'])) {
                $notes .= "\n\nOrder Items:\n" . json_encode($validated['items'], JSON_PRETTY_PRINT);
            }
            if (isset($validated['total_amount'])) {
                $notes .= "\n\nTotal Amount: $" . number_format($validated['total_amount'], 2);
            }
            $orderData['notes'] = trim($notes);

            // Create the order
            $order = Order::query()->create($orderData);

            // Create an initial event
            OrderEvent::query()->create([
                'order_id' => $order->id,
                'type' => 'created',
                'meta' => [
                    'status' => 'UNASSIGNED',
                    'source' => 'pos_webhook',
                    'pos_order_id' => $validated['pos_order_id'],
                ],
            ]);

            // Broadcast the new order
            broadcast(new OrderStatusChanged($order->fresh()))->toOthers();

            DB::commit();

            Log::info("Order created from POS webhook", [
                'order_id' => $order->id,
                'pos_order_id' => $validated['pos_order_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully in dispatch system',
                'order_id' => $order->id,
                'order_code' => $order->code,
                'status' => $order->status,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("POS webhook error: " . $e->getMessage(), [
                'request_data' => $request->all(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Health check endpoint for a POS system to test connectivity
     */
    public function healthCheck()
    {
        return response()->json([
            'success' => true,
            'message' => 'Dispatch system webhook is operational',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
