<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_participants', function (Blueprint $table) {
            if (Schema::connection('pgsql_trivia')->hasColumn('trivia_participants', 'team_id')) {
                return;
            }

            $table->ulid('team_id')->nullable()->after('room_id');
            $table->foreign('team_id')->references('id')->on('trivia_teams')->onDelete('set null');
            $table->index('team_id');
            $table->index(['room_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_participants', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
