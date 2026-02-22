<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balance_topup_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('coins')->comment('Базовое количество монет');
            $table->decimal('price', 12, 2)->comment('Цена в рублях');
            $table->unsignedTinyInteger('bonus_percent')->default(0)->comment('Бонус %: чем больше пакет, тем больше получаешь');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('balance_topups', function (Blueprint $table) {
            $table->unsignedInteger('coins')->nullable()->after('amount')->comment('Монет к зачислению (с учётом бонуса)');
        });
    }

    public function down(): void
    {
        Schema::table('balance_topups', function (Blueprint $table) {
            $table->dropColumn('coins');
        });
        Schema::dropIfExists('balance_topup_packages');
    }
};
