<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function fillRawPasswords(): void
    {
        // Try to update existing known accounts with their raw passwords so they are visible
        try {
            \Illuminate\Support\Facades\DB::table('users')
                ->where('phone', 'admin@uzagromind.uz')
                ->update(['plain_password' => 'uzagromind4321']);

            \Illuminate\Support\Facades\DB::table('users')
                ->where('phone', 'amudaryo_monitor')
                ->update(['plain_password' => 'secretpassword']);

            \Illuminate\Support\Facades\DB::table('users')
                ->where('phone', 'shumanay_monitor')
                ->update(['plain_password' => 'secretpassword']);
                
            \Illuminate\Support\Facades\DB::table('users')
                ->where('phone', '998901111111')
                ->update(['plain_password' => 'secret123']);
                
            \Illuminate\Support\Facades\DB::table('users')
                ->where('phone', '998880000000')
                ->update(['plain_password' => 'secret123']);
                
            \Illuminate\Support\Facades\DB::table('users')
                ->where('phone', '998941490202')
                ->update(['plain_password' => 'secret123']);
                
            \Illuminate\Support\Facades\DB::table('users')
                ->where('phone', '998222222222')
                ->update(['plain_password' => 'secret123']);
        } catch (\Exception $e) {
            // Ignore if tables or records don't exist yet
        }
    }

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plain_password')->nullable()->after('password');
        });

        $this->fillRawPasswords();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('plain_password');
        });
    }
};
