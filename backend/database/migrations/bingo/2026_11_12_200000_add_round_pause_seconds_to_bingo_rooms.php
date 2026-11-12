<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Pausa entre rondas configurable por sala (y por plantilla automática).
 * null = usar el valor global config('bingo.round_pause_seconds').
 */
return new class extends Migration
{
    private const TABLES = ['bingo_rooms_config', 'bingo_rooms_automatic'];

    public function down() : void
    {
        foreach (self::TABLES as $tableName) {
            if (Schema::hasColumn($tableName, 'round_pause_seconds')) {
                Schema::table($tableName, function(Blueprint $table) {
                    $table->dropColumn('round_pause_seconds');
                });
            }
        }
    }

    public function up() : void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasColumn($tableName, 'round_pause_seconds')) {
                Schema::table($tableName, function(Blueprint $table) {
                    $table->unsignedSmallInteger('round_pause_seconds')->nullable()->after('time_chip')
                        ->comment('Segundos de pausa entre rondas; null = valor global (config bingo.round_pause_seconds)');
                });
            }
        }
    }
};
