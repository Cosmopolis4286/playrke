<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        Schema::table('bingo_card_prizes', function(Blueprint $table) {
            $table->dropForeign(['external_player_id']);
            $table->dropIndex('idx_bingo_card_prizes_external_player');
            $table->dropColumn('external_player_id');
        });
    }

    public function up() : void
    {
        Schema::table('bingo_card_prizes', function(Blueprint $table) {
            $table->foreignSnowflake('external_player_id')
                ->nullable()
                ->constrained('external_players')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Comprador externo beneficiario del premio; nunca confundirlo con el vendedor');

            $table->index(
                ['external_player_id', 'prize_status'],
                'idx_bingo_card_prizes_external_player',
            );
        });
    }
};
