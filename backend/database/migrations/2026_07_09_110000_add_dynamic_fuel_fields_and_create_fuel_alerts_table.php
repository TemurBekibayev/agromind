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
        // 1. Add nominal rate and state columns to vehicles table
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('nominal_rate_road', 8, 2)->default(3.50); // Liters per hour
            $table->decimal('nominal_rate_work_light', 8, 2)->default(6.00); // Liters per hour
            $table->decimal('nominal_rate_work_heavy', 8, 2)->default(12.00); // Liters per hour
            $table->decimal('current_fuel_level', 8, 2)->default(50.00); // Current estimated fuel in Liters
            $table->decimal('distance_since_empty', 8, 2)->default(0.00); // Distance driven on 0 fuel (km)
        });

        // 2. Create fuel_alerts table to track suspicious events
        Schema::create('fuel_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('type'); // 'overflow', 'empty_driving', 'discrepancy', 'unlogged_refill'
            $table->string('severity')->default('low'); // 'low', 'medium', 'high'
            $table->string('description');
            $table->decimal('calculated_fuel_before', 8, 2)->nullable();
            $table->decimal('refilled_amount', 8, 2)->nullable();
            $table->decimal('distance_traveled', 8, 2)->nullable();
            $table->string('status')->default('pending_check'); // 'pending_check', 'confirmed', 'rejected'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_alerts');

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'nominal_rate_road',
                'nominal_rate_work_light',
                'nominal_rate_work_heavy',
                'current_fuel_level',
                'distance_since_empty'
            ]);
        });
    }
};
