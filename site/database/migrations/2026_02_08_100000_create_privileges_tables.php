<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privileges', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->json('features')->nullable();
            $table->timestamps();
        });

        Schema::create('privilege_server', function (Blueprint $table) {
            $table->id();
            $table->foreignId('privilege_id')->constrained()->cascadeOnDelete();
            $table->string('server_slug', 255);
            $table->timestamps();
            $table->unique(['privilege_id', 'server_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privilege_server');
        Schema::dropIfExists('privileges');
    }
};
