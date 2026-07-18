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
        if (!Schema::hasTable('private_messages')) {
            Schema::create('private_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
                $table->text('message')->nullable();
                $table->boolean('is_voice')->default(false);
                $table->integer('voice_duration')->nullable();
                $table->string('audio_path')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        } else {
            Schema::table('private_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('private_messages', 'is_voice')) {
                    $table->boolean('is_voice')->default(false);
                }
                if (!Schema::hasColumn('private_messages', 'voice_duration')) {
                    $table->integer('voice_duration')->nullable();
                }
                if (!Schema::hasColumn('private_messages', 'audio_path')) {
                    $table->string('audio_path')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('private_messages');
    }
};
