<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'game_mode')) {
                $table->string('game_mode', 20)->default('free_for_all')->after('status');
            }
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'teams_config')) {
                $table->jsonb('teams_config')->nullable()->after('game_mode');
            }
        });

        \Illuminate\Support\Facades\DB::connection('pgsql_trivia')->statement(
            'CREATE INDEX IF NOT EXISTS trivia_rooms_game_mode_index ON trivia_rooms (game_mode)'
        );
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            $cols = array_filter(['game_mode', 'teams_config'], fn($c) =>
                Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', $c)
            );
            if ($cols) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
