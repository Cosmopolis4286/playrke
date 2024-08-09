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
        Schema::create('bingo_off_prize_ctns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room');
            $table->unsignedBigInteger('commerce');
            $table->unsignedBigInteger('seller');
            $table->unsignedBigInteger('player_link');
            $table->unsignedBigInteger('lot');
            $table->unsignedBigInteger('ctn');
            $table->unsignedBigInteger('prize_type');
            $table->unsignedBigInteger('coin');
            $table->double('amount');
            $table->boolean('status')->default(0);
            $table->timestamps();

            $table->foreign('room')->references('id')->on('bingo_off_rooms');
            $table->foreign('commerce')->references('id')->on('commerces');
            $table->foreign('player_link')->references('id')->on('player_link');
            $table->foreign('seller')->references('id')->on('sellers');
            $table->foreign('lot')->references('id')->on('bingo_off_ctns_lot');
            $table->foreign('ctn')->references('id')->on('bingo_off_ctns');
            $table->foreign('prize_type')->references('id')->on('game_prizes')->onUpdate('cascade');
            $table->foreign('coin')->references('id')->on('coins');

            // Índice compuesto
            $table->index(['room', 'commerce']);
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bingo_off_prize_ctns');
    }
};
