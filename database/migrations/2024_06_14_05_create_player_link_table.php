<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayerLinkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('player_link', function (Blueprint $table) {
            $table->id();
            $table->string('link', 25);
            $table->unsignedBigInteger('country');
            $table->unsignedBigInteger('coin');
            $table->unsignedBigInteger('game');
            $table->unsignedBigInteger('game_mode');
            $table->unsignedBigInteger('room');
            $table->unsignedBigInteger('commerce');
            $table->unsignedBigInteger('seller');
            $table->string('name', 20);
            $table->string('lastname', 20);
            $table->string('identification', 12)->nullable();
            $table->string('phone', 14);
            $table->timestamps();

            // Foreign keys
            $table->foreign('country')->references('id')->on('countries');
            $table->foreign('coin')->references('id')->on('coins');
            $table->foreign('game')->references('id')->on('games');
            $table->foreign('game_mode')->references('id')->on('game_modes');
            $table->foreign('room')->references('id')->on('bingo_off_rooms');
            $table->foreign('commerce')->references('id')->on('commerces');
            $table->foreign('seller')->references('id')->on('sellers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('player_link');
    }
};
