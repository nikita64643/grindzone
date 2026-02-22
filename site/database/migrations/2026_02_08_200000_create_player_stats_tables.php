<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_playtime_minutes')->default(0);
            $table->unsignedInteger('last10_days_playtime_minutes')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('player_playtime_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('minutes')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_playtime_daily');
        Schema::dropIfExists('player_stats');
    }
};
