<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Try to drop spatial index if it exists (spatial indexes can't be on nullable columns)
        try {
            Schema::table('riders', function (Blueprint $table) {
                $table->dropSpatialIndex(['latest_pos']);
            });
        } catch (\Exception $e) {
            // Index might already be dropped, continue
        }

        // Make latest_pos column nullable
        DB::statement('ALTER TABLE riders MODIFY latest_pos POINT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to NOT NULL
        DB::statement('ALTER TABLE riders MODIFY latest_pos POINT NOT NULL');

        // Recreate spatial index
        Schema::table('riders', function (Blueprint $table) {
            $table->spatialIndex('latest_pos');
        });
    }
};
