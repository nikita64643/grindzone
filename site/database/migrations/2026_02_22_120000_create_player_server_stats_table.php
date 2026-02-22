<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_server_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('server_slug', 64)->index();
            $table->unsignedBigInteger('playtime_minutes')->default(0);
            $table->unsignedBigInteger('mob_kills')->default(0);
            $table->unsignedInteger('votes')->default(0);
            $table->decimal('silver', 18, 2)->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'server_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_server_stats');
    }
};
