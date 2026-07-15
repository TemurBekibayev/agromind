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
        Schema::create('fuel_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('fuel_amount', 8, 2);
            $table->timestamp('refilled_at');
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('fuel_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('type'); // 'discrepancy', 'overflow'
            $table->string('severity'); // 'low', 'medium', 'high'
            $table->text('description');
            $table->decimal('calculated_fuel_before', 8, 2);
            $table->decimal('refilled_amount', 8, 2);
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
        Schema::dropIfExists('fuel_entries');
    }
};
