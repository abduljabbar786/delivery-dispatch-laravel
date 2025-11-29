<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cleanup
                            {--months=1 : Number of months to keep completed orders}
                            {--keep-failed : Keep failed orders indefinitely}
                            {--dry-run : Run without actually deleting records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old completed and delivered orders to optimize database performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $months = (int) $this->option('months');
        $keepFailed = $this->option('keep-failed');
        $dryRun = $this->option('dry-run');

        $this->info("Orders Cleanup Process Started");
        $this->info("Retention: {$months} month(s) for completed orders");

        if ($dryRun) {
            $this->warn("DRY RUN MODE - No records will be deleted");
        }

        $cutoffDate = now()->subMonths($months);
        $this->info("Cutoff Date: {$cutoffDate->toDateTimeString()}");

        // Build query for orders to delete
        $query = Order::where('created_at', '<', $cutoffDate)
            ->whereIn('status', ['DELIVERED', 'FAILED']);

        if ($keepFailed) {
            $query->where('status', '!=', 'FAILED');
            $this->info("Keeping failed orders (--keep-failed option enabled)");
        }

        // Count records to be deleted
        $count = $query->count();

        if ($count === 0) {
            $this->info("No old orders found to clean up.");
            return Command::SUCCESS;
        }

        // Show breakdown by status
        $breakdown = Order::where('created_at', '<', $cutoffDate)
            ->whereIn('status', $keepFailed ? ['DELIVERED'] : ['DELIVERED', 'FAILED'])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $this->line("Found {$count} orders older than {$months} month(s):");
        foreach ($breakdown as $row) {
            $this->line("  - {$row->status}: {$row->count}");
        }

        if ($dryRun) {
            $this->info("Would delete {$count} records (dry run - no deletion performed)");
            return Command::SUCCESS;
        }

        // Confirm deletion
        if (!$this->confirm("Delete {$count} old orders?", true)) {
            $this->warn("Cleanup cancelled by user");
            return Command::SUCCESS;
        }

        $this->info("Deleting old orders...");

        // Delete in chunks to avoid locking table for too long
        $deletedCount = 0;
        $chunkSize = 1000;

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        while (true) {
            $query = Order::where('created_at', '<', $cutoffDate)
                ->whereIn('status', $keepFailed ? ['DELIVERED'] : ['DELIVERED', 'FAILED'])
                ->limit($chunkSize);

            $deleted = $query->delete();

            if ($deleted === 0) {
                break;
            }

            $deletedCount += $deleted;
            $bar->advance($deleted);

            // Small delay to reduce database load
            usleep(100000); // 0.1 seconds
        }

        $bar->finish();
        $this->newLine(2);

        // Optimize table after deletion
        $this->info("Optimizing orders table...");
        DB::statement('OPTIMIZE TABLE orders');

        // Get final statistics
        $remainingCount = Order::count();
        $statusBreakdown = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $this->info("Cleanup completed successfully!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Records Deleted', number_format($deletedCount)],
                ['Records Remaining', number_format($remainingCount)],
                ['Cutoff Date', $cutoffDate->toDateTimeString()],
            ]
        );

        $this->line("\nRemaining Orders by Status:");
        foreach ($statusBreakdown as $status => $count) {
            $this->line("  - {$status}: {$count}");
        }

        return Command::SUCCESS;
    }
}
