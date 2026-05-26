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
        Schema::create('gps_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->double('latitude', 10, 8);
            $table->double('longitude', 11, 8);
            $table->decimal('speed', 5, 2)->default(0.00); // km/h
            $table->decimal('fuel_level', 5, 2); // percent or liters
            $table->integer('signal_strength')->default(100); // scale 0-100 or dBm
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            // Indexes for faster dashboard/history queries
            $table->index(['vehicle_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gps_tracks');
    }
};
