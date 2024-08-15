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
        Schema::create('bingo_sort_code', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_mode');
            $table->unsignedBigInteger('room');
            $table->string('num', 1);
            $table->string('sym', 6);
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
        Schema::dropIfExists('bingo_sort_code');
    }
};
