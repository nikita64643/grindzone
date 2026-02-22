<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('minecraft_servers', function (Blueprint $table) {
            $table->unsignedInteger('easydonate_server_id')->default(0)->after('sort_order');
        });

        Schema::table('privileges', function (Blueprint $table) {
            $table->unsignedInteger('easydonate_product_id')->default(0)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('minecraft_servers', function (Blueprint $table) {
            $table->dropColumn('easydonate_server_id');
        });

        Schema::table('privileges', function (Blueprint $table) {
            $table->dropColumn('easydonate_product_id');
        });
    }
};
