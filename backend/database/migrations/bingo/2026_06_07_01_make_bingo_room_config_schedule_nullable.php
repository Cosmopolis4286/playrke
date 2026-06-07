<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite salas de bingo sin horario de inicio programado:
     * - date/time/unix_date pasan a ser nullable
     * - start_mode indica si la sala arranca por horario (SCHEDULED)
     *   o por activación manual del anfitrión/admin (MANUAL)
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE bingo_rooms_config MODIFY COLUMN date DATE NULL COMMENT 'Fecha del juego (null si la sala es de inicio manual)'");
        DB::statement("ALTER TABLE bingo_rooms_config MODIFY COLUMN time VARCHAR(10) NULL COMMENT 'Hora del juego (null si la sala es de inicio manual)'");
        DB::statement("ALTER TABLE bingo_rooms_config MODIFY COLUMN unix_date INT UNSIGNED NULL COMMENT 'Fecha en formato timestamp UNIX (null si la sala es de inicio manual)'");

        if (!Schema::hasColumn('bingo_rooms_config', 'start_mode')) {
            Schema::table('bingo_rooms_config', function (Blueprint $table) {
                $table->enum('start_mode', ['SCHEDULED', 'MANUAL'])
                    ->default('SCHEDULED')
                    ->after('in_live')
                    ->comment('SCHEDULED = inicia por horario programado, MANUAL = requiere activación manual del anfitrión/admin');

                $table->index('start_mode', 'idx_bingo_rooms_config_start_mode');
            });
        }
    }

    public function down(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->dropIndex('idx_bingo_rooms_config_start_mode');
            $table->dropColumn('start_mode');
        });

        DB::statement("ALTER TABLE bingo_rooms_config MODIFY COLUMN date DATE NOT NULL COMMENT 'Fecha del juego'");
        DB::statement("ALTER TABLE bingo_rooms_config MODIFY COLUMN time VARCHAR(10) NOT NULL COMMENT 'Hora del juego'");
        DB::statement("ALTER TABLE bingo_rooms_config MODIFY COLUMN unix_date INT UNSIGNED NOT NULL COMMENT 'Fecha en formato timestamp UNIX'");
    }
};
