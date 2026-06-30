<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PRIZE es transversal a todos los juegos (Bingo, Trivia, etc. otorgan
     * premio con el mismo tipo) — pasa de game_group=BINGO a null para que
     * la UI lo muestre una sola vez en la sección "General" en vez de
     * duplicarlo por cada juego. PURCHASE permanece como BINGO (exclusivo).
     */
    public function up(): void
    {
        DB::table('movement_types')->where('code', 'PRIZE')->update([
            'game_group' => null,
            'sort_order' => 30,
        ]);

        DB::table('movement_types')->where('code', 'PURCHASE')->update([
            'sort_order' => 40,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('movement_types')->where('code', 'PRIZE')->update([
            'game_group' => 'BINGO',
            'sort_order' => 40,
        ]);

        DB::table('movement_types')->where('code', 'PURCHASE')->update([
            'sort_order' => 30,
        ]);
    }
};
