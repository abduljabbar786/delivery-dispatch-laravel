<?php

namespace App\Console\Commands;

use App\Models\RiderLocation;
use Illuminate\Console\Command;

class CleanupRiderLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rider-locations:cleanup {--days=1 : Number of days to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old rider location data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info("Cleaning up rider locations older than {$days} day(s)...");

        $cutoffDate = now()->subDays($days);

        $deletedCount = RiderLocation::where('created_at', '<', $cutoffDate)->delete();

        $this->info("Successfully deleted {$deletedCount} rider location records.");

        return Command::SUCCESS;
    }
}
