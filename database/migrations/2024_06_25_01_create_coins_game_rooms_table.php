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
        Schema::create('coins_game_rooms', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('game');
            $table->unsignedBigInteger('game_mode')->nullable();
            $table->integer('room');
            $table->unsignedBigInteger('coin');
            $table->timestamps();

            $table->foreign('game')->references('id')->on('games');
            $table->foreign('game_mode')->references('id')->on('game_modes');
            $table->foreign('coin')->references('id')->on('coins');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coins_game_rooms');
    }
};
