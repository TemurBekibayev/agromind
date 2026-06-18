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
        Schema::create('water_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->onDelete('cascade');
            $table->integer('year');
            $table->integer('month'); // 1-12 (Aprel=4, May=5, etc.)
            $table->integer('decade'); // 1, 2, 3
            $table->string('water_source'); // 'surface' (er usti), 'groundwater' (er osti), 'drainage' (kollektor-drenaj)
            $table->double('limit_m3', 15, 2)->default(0.00);
            $table->double('used_m3', 15, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_records');
    }
};
