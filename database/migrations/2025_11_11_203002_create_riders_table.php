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
        Schema::create('riders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique()->nullable();
            $table->enum('status', ['OFFLINE', 'IDLE', 'BUSY'])->default('OFFLINE');
            $table->timestamp('last_seen_at')->nullable();
            $table->double('latest_lat')->nullable();
            $table->double('latest_lng')->nullable();
            $table->geometry('latest_pos', subtype: 'point', srid: 4326);
            $table->smallInteger('battery')->nullable();
            $table->timestamps();

            $table->spatialIndex('latest_pos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riders');
    }
};
