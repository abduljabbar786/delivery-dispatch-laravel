<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('address')->nullable();
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->geometry('dest_pos', subtype: 'point', srid: 4326);
            $table->enum('status', [
                'UNASSIGNED',
                'ASSIGNED',
                'PICKED_UP',
                'OUT_FOR_DELIVERY',
                'DELIVERED',
                'FAILED'
            ])->default('UNASSIGNED');
            $table->foreignId('assigned_rider_id')->nullable()->constrained('riders')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->spatialIndex('dest_pos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
