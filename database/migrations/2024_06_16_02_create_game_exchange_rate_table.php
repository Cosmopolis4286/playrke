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
        Schema::create('game_exchange_rate', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('game');
            $table->unsignedBigInteger('room');
            $table->unsignedBigInteger('coin');
            $table->double('exchange_rate');
            $table->timestamps();

            $table->foreign('game')->references('id')->on('games');
            $table->foreign('room')->references('id')->on('bingo_off_rooms');
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
        Schema::dropIfExists('game_exchange_rate');
    }
};
