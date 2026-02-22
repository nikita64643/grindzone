<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privilege_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('server_slug', 64);
            $table->string('server_name', 255);
            $table->string('privilege_key', 64);
            $table->string('privilege_name', 255);
            $table->decimal('amount', 12, 2);
            $table->string('order_id', 64)->unique()->comment('MNT_TRANSACTION_ID для Moneta');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privilege_purchases');
    }
};
