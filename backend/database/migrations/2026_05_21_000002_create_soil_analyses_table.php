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
        Schema::create('soil_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->onDelete('cascade');
            $table->string('target_crop');
            $table->decimal('ph', 4, 2);
            $table->decimal('fertility', 5, 2); // score or percentage
            $table->decimal('moisture', 5, 2);  // percentage
            $table->decimal('temperature', 5, 2); // celsius
            $table->decimal('sunlight', 8, 2);   // lux or index
            $table->decimal('humidity', 5, 2);   // percentage
            $table->date('analysis_date');
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soil_analyses');
    }
};
