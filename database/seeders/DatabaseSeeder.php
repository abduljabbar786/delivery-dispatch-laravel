<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,        // Must run first - creates branches
            UserSeeder::class,           // Depends on BranchSeeder
            RiderSeeder::class,          // Depends on UserSeeder
            OrderSeeder::class,          // Depends on RiderSeeder and BranchSeeder
            RestaurantSettingsSeeder::class, // Can run anytime after BranchSeeder
        ]);
    }
}
