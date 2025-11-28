<?php

use App\Models\Order;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('full order lifecycle from ASSIGNED to DELIVERED sets rider to IDLE', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    $riderUser = User::factory()->create(['role' => 'rider', 'email' => '+923001234567']);
    $rider = Rider::query()->create([
        'user_id' => $riderUser->id,
        'name' => 'Test Rider',
        'phone' => '+923001234567',
        'status' => 'IDLE',
        'latest_lat' => 24.8607,
        'latest_lng' => 67.0011,
        'latest_pos' => DB::raw("ST_GeomFromText('POINT(67.0011 24.8607)', 4326)"),
    ]);

    $order = Order::query()->create([
        'code' => 'ORD-001',
        'customer_name' => 'Customer 1',
        'status' => 'UNASSIGNED',
        'lat' => 40.7589,
        'lng' => -73.9851,
        'dest_pos' => DB::raw("ST_GeomFromText('POINT(-73.9851 40.7589)', 4326)"),
    ]);

    // Assign order
    $this->actingAs($user, 'sanctum')
        ->postJson("/api/orders/{$order->id}/assign", [
            'rider_id' => $rider->id,
        ])
        ->assertStatus(200);

    expect($order->fresh()->status)->toBe('ASSIGNED')
        ->and($rider->fresh()->status)->toBe('BUSY');

    // Update to PICKED_UP
    $this->actingAs($user, 'sanctum')
        ->postJson("/api/orders/{$order->id}/status", [
            'status' => 'PICKED_UP',
        ])
        ->assertStatus(200);

    expect($order->fresh()->status)->toBe('PICKED_UP')
        ->and($rider->fresh()->status)->toBe('BUSY');

    // Update to OUT_FOR_DELIVERY
    $this->actingAs($user, 'sanctum')
        ->postJson("/api/orders/{$order->id}/status", [
            'status' => 'OUT_FOR_DELIVERY',
        ])
        ->assertStatus(200);

    expect($order->fresh()->status)->toBe('OUT_FOR_DELIVERY')
        ->and($rider->fresh()->status)->toBe('BUSY');

    // Update to DELIVERED
    $this->actingAs($user, 'sanctum')
        ->postJson("/api/orders/{$order->id}/status", [
            'status' => 'DELIVERED',
        ])
        ->assertStatus(200);

    expect($order->fresh()->status)->toBe('DELIVERED')
        ->and($rider->fresh()->status)->toBe('IDLE');
});

test('order status FAILED also sets rider to IDLE', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    $riderUser = User::factory()->create(['role' => 'rider', 'email' => '+923001234567']);
    $rider = Rider::query()->create([
        'user_id' => $riderUser->id,
        'name' => 'Test Rider',
        'phone' => '+923001234567',
        'status' => 'IDLE',
        'latest_lat' => 24.8607,
        'latest_lng' => 67.0011,
        'latest_pos' => DB::raw("ST_GeomFromText('POINT(67.0011 24.8607)', 4326)"),
    ]);

    $order = Order::query()->create([
        'code' => 'ORD-001',
        'customer_name' => 'Customer 1',
        'status' => 'UNASSIGNED',
        'lat' => 40.7589,
        'lng' => -73.9851,
        'dest_pos' => DB::raw("ST_GeomFromText('POINT(-73.9851 40.7589)', 4326)"),
    ]);

    // Assign and fail order
    $this->actingAs($user, 'sanctum')
        ->postJson("/api/orders/{$order->id}/assign", [
            'rider_id' => $rider->id,
        ]);

    expect($rider->fresh()->status)->toBe('BUSY');

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/orders/{$order->id}/status", [
            'status' => 'FAILED',
            'reason' => 'Customer not available',
        ])
        ->assertStatus(200);

    expect($order->fresh()->status)->toBe('FAILED')
        ->and($rider->fresh()->status)->toBe('IDLE');
});
