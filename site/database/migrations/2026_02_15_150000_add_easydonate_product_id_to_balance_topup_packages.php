<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('balance_topup_packages', function (Blueprint $table) {
            $table->unsignedInteger('easydonate_product_id')->default(0)->after('bonus_percent');
        });
    }

    public function down(): void
    {
        Schema::table('balance_topup_packages', function (Blueprint $table) {
            $table->dropColumn('easydonate_product_id');
        });
    }
};
