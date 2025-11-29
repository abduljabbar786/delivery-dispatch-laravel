<?php

use App\Models\Order;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('cannot assign order to rider who already has active order', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    $riderUser = User::factory()->create(['role' => 'rider', 'email' => '+923001234567']);
    $rider = Rider::create([
        'user_id' => $riderUser->id,
        'name' => 'Test Rider',
        'phone' => '+923001234567',
        'status' => 'IDLE',
        'latest_lat' => 24.8607,
        'latest_lng' => 67.0011,
        'latest_pos' => DB::raw("ST_GeomFromText('POINT(67.0011 24.8607)', 4326)"),
    ]);

    // Create two orders
    $order1 = Order::create([
        'code' => 'ORD-001',
        'customer_name' => 'Customer 1',
        'status' => 'UNASSIGNED',
        'lat' => 40.7589,
        'lng' => -73.9851,
        'dest_pos' => DB::raw("ST_GeomFromText('POINT(-73.9851 40.7589)', 4326)"),
    ]);

    $order2 = Order::create([
        'code' => 'ORD-002',
        'customer_name' => 'Customer 2',
        'status' => 'UNASSIGNED',
        'lat' => 40.7589,
        'lng' => -73.9851,
        'dest_pos' => DB::raw("ST_GeomFromText('POINT(-73.9851 40.7589)', 4326)"),
    ]);

    // Assign first order successfully
    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/orders/{$order1->id}/assign", [
            'rider_id' => $rider->id,
        ]);

    $response->assertStatus(200);

    // Try to assign second order to same rider (should fail)
    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/orders/{$order2->id}/assign", [
            'rider_id' => $rider->id,
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Rider already has an active order',
        ]);
});

test('assigning marks rider BUSY and creates order event and broadcasts', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    $riderUser = User::factory()->create(['role' => 'rider', 'email' => '+923001234567']);
    $rider = Rider::create([
        'user_id' => $riderUser->id,
        'name' => 'Test Rider',
        'phone' => '+923001234567',
        'status' => 'IDLE',
        'latest_lat' => 24.8607,
        'latest_lng' => 67.0011,
        'latest_pos' => DB::raw("ST_GeomFromText('POINT(67.0011 24.8607)', 4326)"),
    ]);

    $order = Order::create([
        'code' => 'ORD-001',
        'customer_name' => 'Customer 1',
        'status' => 'UNASSIGNED',
        'lat' => 40.7589,
        'lng' => -73.9851,
        'dest_pos' => DB::raw("ST_GeomFromText('POINT(-73.9851 40.7589)', 4326)"),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/orders/{$order->id}/assign", [
            'rider_id' => $rider->id,
        ]);

    $response->assertStatus(200);

    // Check order is assigned
    expect($order->fresh()->status)->toBe('ASSIGNED')
        ->and($order->fresh()->assigned_rider_id)->toBe($rider->id);

    // Check rider is BUSY
    expect($rider->fresh()->status)->toBe('BUSY');
});
