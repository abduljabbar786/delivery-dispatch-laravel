<?php

use App\Helpers\RestaurantHelper;
use App\Models\RiderLocation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cleanup rider locations daily at restaurant closing time
Schedule::call(function () {
    $deletedCount = RiderLocation::whereDate('created_at', '<', today())->delete();

    info("Daily rider locations cleanup completed. Deleted {$deletedCount} records.");
})->dailyAt('04:30') // Run at 4:30 AM (30 mins after typical closing time)
  ->name('cleanup-rider-locations')
  ->onOneServer();

// Optional: Archive old orders (keep last 90 days)
// Uncomment if you want to enable this
// Schedule::call(function () {
//     $cutoffDate = now()->subDays(90);
//     $deletedOrders = \App\Models\Order::where('created_at', '<', $cutoffDate)
//         ->whereIn('status', ['DELIVERED', 'FAILED'])
//         ->delete();
//
//     info("Archived {$deletedOrders} old orders.");
// })->weekly()->mondays()->at('05:00')
//   ->name('archive-old-orders')
//   ->onOneServer();
