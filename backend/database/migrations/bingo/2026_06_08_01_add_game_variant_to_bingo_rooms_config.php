<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->string('game_variant', 20)
                ->default('BINGO_75')
                ->after('game_mode_id')
                ->comment('Variante de bingo: BINGO_75, BINGO_90, BINGO_80, BINGO_30');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->dropColumn('game_variant');
        });
    }
};
