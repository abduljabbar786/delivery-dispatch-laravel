<?php

namespace Database\Seeders;

use App\Models\RestaurantSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RestaurantSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Restaurant operating hours: 4 PM to 4 AM
        RestaurantSetting::set(
            'opening_time',
            '16:00',
            'Restaurant opening time (24-hour format)'
        );

        RestaurantSetting::set(
            'closing_time',
            '04:00',
            'Restaurant closing time (24-hour format)'
        );

        RestaurantSetting::set(
            'restaurant_name',
            'LFC',
            'Restaurant name'
        );
    }
}
