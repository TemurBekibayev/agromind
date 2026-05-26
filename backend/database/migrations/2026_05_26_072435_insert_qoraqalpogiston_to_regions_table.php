<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\Region;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Region::firstOrCreate([
            'name' => 'Qoraqalpog\'iston Respublikasi',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Region::where('name', 'Qoraqalpog\'iston Respublikasi')->delete();
    }
};
