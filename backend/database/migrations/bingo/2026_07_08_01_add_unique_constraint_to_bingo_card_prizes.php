<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_card_prizes', function (Blueprint $table) {
            $table->unique(['winning_card_id', 'round_id'], 'bingo_card_prizes_card_round_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_card_prizes', function (Blueprint $table) {
            $table->dropUnique('bingo_card_prizes_card_round_unique');
        });
    }
};
