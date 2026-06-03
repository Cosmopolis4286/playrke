<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('scheduled_at');
            }
            if (!Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('started_at');
            }
        });

        // Renombrar config → settings solo si config existe y settings no existe todavía
        $conn = DB::connection('pgsql_trivia');
        $hasConfig   = (bool) $conn->selectOne("SELECT 1 FROM information_schema.columns WHERE table_name='trivia_rooms' AND column_name='config'");
        $hasSettings = (bool) $conn->selectOne("SELECT 1 FROM information_schema.columns WHERE table_name='trivia_rooms' AND column_name='settings'");

        if ($hasConfig && !$hasSettings) {
            $conn->statement('ALTER TABLE trivia_rooms RENAME COLUMN config TO settings');
        }
    }

    public function down(): void
    {
        $conn = DB::connection('pgsql_trivia');
        $hasSettings = (bool) $conn->selectOne("SELECT 1 FROM information_schema.columns WHERE table_name='trivia_rooms' AND column_name='settings'");
        $hasConfig   = (bool) $conn->selectOne("SELECT 1 FROM information_schema.columns WHERE table_name='trivia_rooms' AND column_name='config'");

        if ($hasSettings && !$hasConfig) {
            $conn->statement('ALTER TABLE trivia_rooms RENAME COLUMN settings TO config');
        }

        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            $cols = array_filter(['started_at', 'ended_at'], fn($c) =>
                Schema::connection('pgsql_trivia')->hasColumn('trivia_rooms', $c)
            );
            if ($cols) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
