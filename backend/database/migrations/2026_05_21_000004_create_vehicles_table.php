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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['tractor', 'combine', 'other'])->default('tractor');
            $table->string('plate_number')->unique();
            $table->string('gps_device_id')->nullable()->unique(); // unique to avoid mapping errors
            $table->decimal('fuel_capacity', 8, 2); // capacity in liters
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
