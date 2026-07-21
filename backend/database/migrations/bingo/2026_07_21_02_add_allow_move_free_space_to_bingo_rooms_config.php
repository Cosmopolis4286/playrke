<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            // Solo aplica si allow_custom_card = true y game_variant = BINGO_75
            // (única variante con casilla libre). El jugador puede reubicar la
            // libre a cualquier celda del cartón al personalizarlo.
            $table->boolean('allow_move_free_space')->default(false)->after('custom_card_every_x');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->dropColumn('allow_move_free_space');
        });
    }
};
