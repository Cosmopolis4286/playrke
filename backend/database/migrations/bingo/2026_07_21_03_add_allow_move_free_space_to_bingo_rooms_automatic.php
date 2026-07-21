<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->boolean('allow_move_free_space')->default(false)->after('custom_card_every_x');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->dropColumn('allow_move_free_space');
        });
    }
};
