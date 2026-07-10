<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear old test data first to prevent unique key violations
        DB::table('water_records')->truncate();

        Schema::table('water_records', function (Blueprint $table) {
            $table->dropColumn(['decade', 'water_source']);
            
            // Add unique key to enforce one record per month per farm
            $table->unique(['farm_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('water_records', function (Blueprint $table) {
            $table->dropUnique(['farm_id', 'year', 'month']);
            
            $table->integer('decade')->default(1)->after('month');
            $table->string('water_source')->default('surface')->after('decade');
        });
    }
};
