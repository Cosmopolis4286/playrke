<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTA: Esta tabla está obsoleta. Los datos se consolidaron en
     * bingo_rooms_automatic (campos JSON: rounds_config, prize_types, etc.)
     * La migración se mantiene vacía por orden cronológico.
     */
    public function up(): void
    {
        // Tabla obsoleta - datos consolidados en bingo_rooms_automatic
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se creó tabla, no hay nada que eliminar
    }
};
