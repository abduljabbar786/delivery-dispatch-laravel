<?php

use App\Helpers\RestaurantHelper;
use App\Models\RiderLocation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cleanup rider locations daily at 4:30 AM
// Keeps only 1 day of location history (deletes older records)
Schedule::command('rider-locations:cleanup --days=1')
    ->dailyAt('04:30')
    ->name('cleanup-rider-locations')
    ->onOneServer();

// Cleanup old completed and delivered orders monthly
// Runs on the 1st of each month at 5:00 AM
// Keeps 1 month of order history (deletes older DELIVERED and FAILED orders)
Schedule::command('orders:cleanup --months=1')
    ->monthlyOn(1, '05:00')
    ->name('cleanup-old-orders')
    ->onOneServer();
