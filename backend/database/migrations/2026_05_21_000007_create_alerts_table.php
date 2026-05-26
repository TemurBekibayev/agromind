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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('farm_id')->nullable()->constrained('farms')->onDelete('cascade');
            $table->enum('type', ['geofence_breach', 'signal_lost', 'low_fuel', 'offline']);
            $table->string('message');
            $table->enum('status', ['active', 'resolved'])->default('active');
            $table->timestamp('triggered_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['farm_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
