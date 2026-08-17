<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `jackpot_pct` (columna existente) sigue siendo el % de ESTA sala que se
     * aporta; esta FK nueva dice A CUÁL jackpot va ese %. nullOnDelete (no
     * restrictOnDelete como prize_pool_type_id): el jackpot es opcional.
     */
    public function up(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->foreignSnowflake('jackpot_type_id')
                ->nullable()
                ->after('jackpot_pct')
                ->constrained('jackpot_types')
                ->nullOnDelete()
                ->comment('Jackpot al que aporta el % configurado en jackpot_pct');

            $table->index('jackpot_type_id', 'idx_bingo_rooms_config_jackpot_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->dropForeign(['jackpot_type_id']);
            $table->dropIndex('idx_bingo_rooms_config_jackpot_type_id');
            $table->dropColumn('jackpot_type_id');
        });
    }
};
