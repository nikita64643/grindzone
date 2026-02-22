<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 12, 2)->default(0)->after('remember_token');
            $table->string('nickname')->nullable()->after('name');
            $table->string('status')->nullable()->after('nickname');
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('server_slug');
            $table->string('server_name');
            $table->string('privilege_key');
            $table->string('privilege_name');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['balance', 'nickname', 'status']);
        });
    }
};
