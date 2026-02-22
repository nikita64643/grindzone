<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('balance_topups', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('coins')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('balance_topups', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
        });
    }
};
