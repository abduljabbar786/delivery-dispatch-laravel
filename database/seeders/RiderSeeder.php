<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RiderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $riders = [
            ['name' => 'John Doe', 'phone' => '+923001234501', 'status' => 'IDLE', 'last_seen_at' => now()->subMinutes(5), 'latest_lat' => 31.5080, 'latest_lng' => 74.3507], // Gulberg
            ['name' => 'Jane Smith', 'phone' => '+923002234502', 'status' => 'IDLE', 'last_seen_at' => now()->subMinutes(10), 'latest_lat' => 31.4817, 'latest_lng' => 74.3139], // Model Town
            ['name' => 'Mike Johnson', 'phone' => '+923003234503', 'status' => 'IDLE', 'last_seen_at' => now()->subMinutes(15), 'latest_lat' => 31.4697, 'latest_lng' => 74.4018], // DHA
            ['name' => 'Sarah Williams', 'phone' => '+923004234504', 'status' => 'IDLE', 'last_seen_at' => now()->subMinutes(20), 'latest_lat' => 31.4671, 'latest_lng' => 74.2681], // Johar Town
            ['name' => 'David Brown', 'phone' => '+923005234505', 'status' => 'IDLE', 'last_seen_at' => now()->subMinutes(25), 'latest_lat' => 31.5525, 'latest_lng' => 74.3374], // Cantt
            ['name' => 'Emily Davis', 'phone' => '+923006234506', 'status' => 'OFFLINE', 'last_seen_at' => now()->subHours(2), 'latest_lat' => 31.4331, 'latest_lng' => 74.3410], // Township
            ['name' => 'Chris Wilson', 'phone' => '+923007234507', 'status' => 'IDLE', 'last_seen_at' => now()->subMinutes(8), 'latest_lat' => 31.5101, 'latest_lng' => 74.3029], // Allama Iqbal Town
            ['name' => 'Jessica Martinez', 'phone' => '+923008234508', 'status' => 'IDLE', 'last_seen_at' => now()->subMinutes(12), 'latest_lat' => 31.5182, 'latest_lng' => 74.3498], // Liberty Market
            ['name' => 'Daniel Anderson', 'phone' => '+923009234509', 'status' => 'OFFLINE', 'last_seen_at' => now()->subHours(1), 'latest_lat' => 31.4416, 'latest_lng' => 74.2863], // Faisal Town
            ['name' => 'Ashley Taylor', 'phone' => '+923010234510', 'status' => 'IDLE', 'last_seen_at' => now()->subMinutes(30), 'latest_lat' => 31.5281, 'latest_lng' => 74.3428], // Wahdat Road
        ];

        foreach ($riders as $riderData) {
            // Find the user with a matching email (which is the phone number)
            $user = \App\Models\User::query()->where('email', $riderData['phone'])->first();

            if (!$user) {
                $this->command->warn("User not found for phone: {$riderData['phone']}");
                continue;
            }

            $lat = $riderData['latest_lat'];
            $lng = $riderData['latest_lng'];
            $riderData['latest_pos'] = \DB::raw("ST_GeomFromText('POINT({$lng} {$lat})', 4326)");
            $riderData['user_id'] = $user->id;
            $riderData['branch_id'] = $user->branch_id; // Get branch_id from user

            \App\Models\Rider::query()->create($riderData);
        }

        $this->command->info('Riders created with branch assignments from their user accounts!');
    }
}
