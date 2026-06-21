<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->boolean('allow_custom_card')->default(false)->after('min_cards_to_start');
            // % adicional sobre el precio base del cartón en la moneda de la sala,
            // aplicado al momento de la compra del cartón personalizado.
            $table->decimal('custom_card_price_pct', 5, 2)->nullable()->after('allow_custom_card');
            // Cartón especial: independiente de allow_custom_card — solo limita la
            // cantidad máxima de cartones especiales que puede tener la sala.
            $table->boolean('allow_special_card')->default(false)->after('custom_card_price_pct');
            $table->tinyInteger('max_special_cards')->unsigned()->nullable()->after('allow_special_card');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->dropColumn(['allow_custom_card', 'custom_card_price_pct', 'allow_special_card', 'max_special_cards']);
        });
    }
};
