<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            $table->string('game_mode', 20)->default('free_for_all')->after('status');
            // free_for_all | teams | duel | tournament
            
            $table->jsonb('teams_config')->nullable()->after('game_mode');
            // Config específica por modo: {team_size: 5, team_count: 4, is_auto_assign: true}
            
            $table->index('game_mode');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            $table->dropColumn(['game_mode', 'teams_config']);
        });
    }
};
