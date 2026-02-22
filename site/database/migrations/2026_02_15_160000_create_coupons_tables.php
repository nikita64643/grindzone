<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->unsignedTinyInteger('discount_percent')->default(0)->comment('% скидка на цену');
            $table->decimal('discount_fixed', 12, 2)->default(0)->comment('Фикс. скидка в рублях');
            $table->unsignedTinyInteger('bonus_percent')->default(0)->comment('% бонус к монетам');
            $table->unsignedInteger('bonus_coins')->default(0)->comment('Доп. монет');
            $table->decimal('min_purchase', 12, 2)->nullable()->comment('Мин. сумма для применения');
            $table->unsignedInteger('max_uses')->nullable()->comment('Макс. использований всего (null = без лимита)');
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedTinyInteger('max_uses_per_user')->nullable()->comment('Макс. раз на пользователя (null = без лимита)');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('context', 32)->default('balance')->comment('balance|privilege');
            $table->decimal('amount', 12, 2)->nullable();
            $table->timestamps();
            $table->index(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};
