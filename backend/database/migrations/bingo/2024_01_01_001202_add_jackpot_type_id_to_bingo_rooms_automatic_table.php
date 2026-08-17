<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Paridad de schema con bingo_rooms_config para salas automáticas.
     */
    public function up(): void
    {
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->foreignSnowflake('jackpot_type_id')
                ->nullable()
                ->after('jackpot_pct')
                ->constrained('jackpot_types')
                ->nullOnDelete()
                ->comment('Jackpot al que aporta el % configurado en jackpot_pct');

            $table->index('jackpot_type_id', 'idx_bingo_rooms_automatic_jackpot_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->dropForeign(['jackpot_type_id']);
            $table->dropIndex('idx_bingo_rooms_automatic_jackpot_type_id');
            $table->dropColumn('jackpot_type_id');
        });
    }
};
