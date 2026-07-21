<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->enum('move_free_space_mode', ['paid', 'by_range', 'free'])
                ->nullable()
                ->after('allow_move_free_space');
            $table->decimal('move_free_space_price_pct', 5, 2)->nullable()->after('move_free_space_mode');
        });

        DB::table('bingo_rooms_automatic')
            ->where('allow_move_free_space', true)
            ->update(['move_free_space_mode' => 'free']);

        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->dropColumn('allow_move_free_space');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->boolean('allow_move_free_space')->default(false)->after('custom_card_every_x');
        });

        DB::table('bingo_rooms_automatic')
            ->whereNotNull('move_free_space_mode')
            ->update(['allow_move_free_space' => true]);

        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->dropColumn(['move_free_space_mode', 'move_free_space_price_pct']);
        });
    }
};
