<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE wallet_movements MODIFY COLUMN type ENUM('DEPOSIT','WITHDRAWAL','PURCHASE','PRIZE','TRIVIA_ENTRY','TRIVIA_PRIZE') NOT NULL COMMENT 'Tipo de movimiento'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE wallet_movements MODIFY COLUMN type ENUM('DEPOSIT','WITHDRAWAL','PURCHASE','PRIZE','TRIVIA_ENTRY') NOT NULL COMMENT 'Tipo de movimiento'");
    }
};
