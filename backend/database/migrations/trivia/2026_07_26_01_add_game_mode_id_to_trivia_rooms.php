<?php

use App\Models\Games\Common\GameMode\GameMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `trivia_rooms.game_mode` (string libre: free_for_all/teams/duel/tournament)
 * pasa a resolverse contra el catálogo dinámico `game_modes` (MySQL, tabla
 * `games`+`game_modes` — ver 2026_07_26_01_adapt_game_modes_to_per_game_catalog.php),
 * ahora administrable por admins en vez de estar hardcodeado en el código PHP.
 *
 * trivia_rooms vive en Postgres (conexión pgsql_trivia) y game_modes vive en
 * MySQL — son motores distintos, por lo que game_mode_id es una referencia
 * "blanda": sin FK física de base de datos, validada en la capa de
 * aplicación (FormRequest + GameModeRepository) en vez de con un constraint.
 *
 * La columna vieja `game_mode` (string) se mantiene por ahora sin dropear,
 * como respaldo durante la transición del código de Trivia al nuevo campo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'game_mode_id')) {
            Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
                $table->unsignedBigInteger('game_mode_id')->nullable()->after('game_mode');
            });
        }

        \Illuminate\Support\Facades\DB::connection('pgsql_trivia')->statement(
            'CREATE INDEX IF NOT EXISTS trivia_rooms_game_mode_id_index ON trivia_rooms (game_mode_id)'
        );

        // Backfill: mapear cada valor string existente al id real en MySQL
        // (game_modes.code, scoped al juego TRIVIA). Los modos de Trivia no
        // existían como catálogo hasta ahora, así que se crean aquí mismo si
        // faltan, antes de resolver el backfill.
        $triviaGameId = DB::table('games')->where('name', 'TRIVIA')->value('id');
        if (!$triviaGameId) {
            return;
        }

        $labels = [
            'free_for_all' => 'Libre',
            'teams'        => 'Equipos',
            'duel'         => 'Duelo',
            'tournament'   => 'Torneo',
        ];

        $usedModes = DB::connection('pgsql_trivia')->table('trivia_rooms')
            ->whereNotNull('game_mode')
            ->distinct()
            ->pluck('game_mode');

        foreach ($usedModes as $code) {
            $label = $labels[$code] ?? ucfirst(str_replace('_', ' ', $code));

            $gameMode = GameMode::firstOrCreate(
                ['game_id' => $triviaGameId, 'code' => strtoupper($code)],
                ['label' => $label, 'status' => 'ACTIVE']
            );

            DB::connection('pgsql_trivia')->table('trivia_rooms')
                ->where('game_mode', $code)
                ->update(['game_mode_id' => $gameMode->id]);
        }
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            if (Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'game_mode_id')) {
                $table->dropColumn('game_mode_id');
            }
        });
    }
};
