<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Agrega 'by_range': recargo % calculado automáticamente según el precio
        // base del cartón (ver BingoCardGenerateService::resolveSmartCustomCardPct),
        // sin que el admin configure un valor manual como en 'paid'.
        DB::statement("ALTER TABLE bingo_rooms_config MODIFY custom_card_mode ENUM('paid', 'by_range', 'every_x', 'free') NULL");
        DB::statement("ALTER TABLE bingo_rooms_automatic MODIFY custom_card_mode ENUM('paid', 'by_range', 'every_x', 'free') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE bingo_rooms_config MODIFY custom_card_mode ENUM('paid', 'every_x', 'free') NULL");
        DB::statement("ALTER TABLE bingo_rooms_automatic MODIFY custom_card_mode ENUM('paid', 'every_x', 'free') NULL");
    }
};
