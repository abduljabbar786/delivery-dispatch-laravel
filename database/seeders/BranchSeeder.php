<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Branch 1 - Shadbagh branch (using env pickup location)
        $branch1 = Branch::create([
            'name' => 'Shadbagh branch',
            'code' => 'SHADBAGH',
            'address' => '123 Main Street, New York, NY',
            'phone' => '+1-555-0101',
            'lat' => env('PICKUP_LOCATION_LAT', 40.7489),
            'lng' => env('PICKUP_LOCATION_LNG', -73.9680),
            'opening_time' => '16:00',
            'closing_time' => '04:00',
            'is_active' => true,
        ]);

        // Update spatial column for branch 1
        DB::statement(
            'UPDATE branches SET pickup_pos = ST_GeomFromText(?, 4326) WHERE id = ?',
            ["POINT({$branch1->lng} {$branch1->lat})", $branch1->id]
        );

        // Create Branch 2 - Bismillah branch
        $branch2 = Branch::create([
            'name' => 'Bismillah branch',
            'code' => 'BISMILLAH',
            'address' => '456 Downtown Avenue, New York, NY',
            'phone' => '+1-555-0102',
            'lat' => 40.7589,
            'lng' => -73.9850,
            'opening_time' => '16:00',
            'closing_time' => '04:00',
            'is_active' => true,
        ]);

        // Update spatial column for branch 2
        DB::statement(
            'UPDATE branches SET pickup_pos = ST_GeomFromText(?, 4326) WHERE id = ?',
            ["POINT({$branch2->lng} {$branch2->lat})", $branch2->id]
        );

        // Update all existing orders to belong to Shadbagh branch
        DB::table('orders')->update(['branch_id' => $branch1->id]);

        // Update all existing riders to belong to Shadbagh branch
        DB::table('riders')->update(['branch_id' => $branch1->id]);

        // Update all existing users to belong to Shadbagh branch (optional)
        DB::table('users')->where('role', 'supervisor')->update(['branch_id' => $branch1->id]);

        $this->command->info('Created 2 branches successfully!');
        $this->command->info('All existing orders and riders assigned to Shadbagh branch.');
    }
}
