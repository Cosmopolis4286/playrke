<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bingo_rooms_prizes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_mode');
            $table->unsignedBigInteger('room');
            $table->boolean('jackpot')->default(1);
            $table->boolean('serials')->default(1);
            $table->boolean('line')->default(0);
            $table->boolean('diagonal')->default(0);
            $table->boolean('four_corners')->default(0);
            $table->boolean('bg_cross')->default(0);
            $table->boolean('sm_cross')->default(0);
            $table->timestamps();

            $table->foreign('game_mode')->references('id')->on('game_modes');
            $table->foreign('room')->references('id')->on('bingo_rooms');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bingo_rooms_prizes');
    }
};
