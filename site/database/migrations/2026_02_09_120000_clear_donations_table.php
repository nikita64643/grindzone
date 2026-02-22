<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Очистка таблицы донатов: на сервере ничего не выдавалось, счётчик донатов у пользователей обнуляется.
     * Донаты учитываются только при успешной выдаче на стороне сервера (RCON).
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('donations')->truncate();
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Восстановление данных невозможно
    }
};
