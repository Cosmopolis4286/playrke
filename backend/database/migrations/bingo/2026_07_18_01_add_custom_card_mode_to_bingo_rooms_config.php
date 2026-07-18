<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            // Modo del cartón personalizado (solo aplica si allow_custom_card = true):
            // - paid: costo adicional (custom_card_price_pct)
            // - every_x: gratis cada X cartones comprados (custom_card_every_x)
            // - free: uso libre, sin restricción ni costo adicional
            $table->enum('custom_card_mode', ['paid', 'every_x', 'free'])
                ->nullable()
                ->after('custom_card_price_pct');
            $table->unsignedSmallInteger('custom_card_every_x')->nullable()->after('custom_card_mode');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->dropColumn(['custom_card_mode', 'custom_card_every_x']);
        });
    }
};
