<?php

use App\Models\Rider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('accepts batch location updates and updates rider', function () {
    $user = User::factory()->create(['role' => 'rider', 'email' => '+923001234567']);
    $rider = Rider::query()->create([
        'user_id' => $user->id,
        'name' => 'Test Rider',
        'phone' => '+923001234567',
        'status' => 'IDLE',
        'latest_lat' => 24.8607,
        'latest_lng' => 67.0011,
        'latest_pos' => DB::raw("ST_GeomFromText('POINT(67.0011 24.8607)', 4326)"),
    ]);

    $locationPoints = [
        ['lat' => 24.8607, 'lng' => 67.0011, 'battery' => 80, 'accuracy' => 10.5],
        ['lat' => 24.8608, 'lng' => 67.0012, 'battery' => 79, 'accuracy' => 11.2],
        ['lat' => 24.8609, 'lng' => 67.0013, 'battery' => 78, 'accuracy' => 9.8],
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/rider/locations', [
            'points' => $locationPoints,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Locations ingested successfully',
            'count' => 3,
        ]);

    // Check rider was updated with latest location
    $rider->refresh();
    expect($rider->latest_lat)->toBe(24.8609)
        ->and($rider->latest_lng)->toBe(67.0013)
        ->and($rider->battery)->toBe(78)
        ->and($rider->last_seen_at)->not->toBeNull();

    // Check locations were stored
    $this->assertDatabaseCount('rider_locations', 3);
    $this->assertDatabaseHas('rider_locations', [
        'rider_id' => $rider->id,
        'lat' => 24.8607,
        'lng' => 67.0011,
    ]);
});

test('batch location ingest respects 50 point limit', function () {
    $user = User::factory()->create(['role' => 'rider', 'email' => '+923001234567']);
    Rider::query()->create([
        'user_id' => $user->id,
        'name' => 'Test Rider',
        'phone' => '+923001234567',
        'status' => 'IDLE',
        'latest_lat' => 24.8607,
        'latest_lng' => 67.0011,
        'latest_pos' => DB::raw("ST_GeomFromText('POINT(67.0011 24.8607)', 4326)"),
    ]);

    // Try to send 51 points (should fail validation)
    $locationPoints = [];
    for ($i = 0; $i < 51; $i++) {
        $locationPoints[] = [
            'lat' => 24.8607 + ($i * 0.0001),
            'lng' => 67.0011 + ($i * 0.0001),
            'battery' => 80,
        ];
    }

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/rider/locations', [
            'points' => $locationPoints,
        ]);

    $response->assertStatus(422); // Validation error
});
