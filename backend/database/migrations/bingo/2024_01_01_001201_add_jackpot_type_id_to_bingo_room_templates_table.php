<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down() : void
    {
        if (! Schema::hasColumn('bingo_room_templates', 'jackpot_type_id')) {
            return;
        }

        Schema::table('bingo_room_templates', function(Blueprint $table) {
            $table->dropForeign(['jackpot_type_id']);
            $table->dropIndex('idx_bingo_room_templates_jackpot_type_id');
            $table->dropColumn('jackpot_type_id');
        });
    }

    /**
     * Run the migrations.
     *
     * Paridad de schema con bingo_rooms_config — sin formulario activo hoy
     * para plantillas, se agrega solo para no dejar el shape inconsistente.
     */
    public function up() : void
    {
        // Guard de idempotencia — sin esto, un migrate:fresh que corre en
        // paralelo con otra sesión que ya deja la columna físicamente
        // creada pero sin registrar en `migrations` revienta con "Column
        // already exists" y aborta toda la corrida de tests antes de
        // llegar al seeder (mismo problema ya visto con receipt_hash).
        if (Schema::hasColumn('bingo_room_templates', 'jackpot_type_id')) {
            return;
        }

        Schema::table('bingo_room_templates', function(Blueprint $table) {
            $table->foreignSnowflake('jackpot_type_id')
                ->nullable()
                ->after('jackpot_pct')
                ->constrained('jackpot_types')
                ->nullOnDelete()
                ->comment('Jackpot al que aporta el % configurado en jackpot_pct');

            $table->index('jackpot_type_id', 'idx_bingo_room_templates_jackpot_type_id');
        });
    }
};
