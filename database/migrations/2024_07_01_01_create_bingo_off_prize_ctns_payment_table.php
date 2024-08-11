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
        Schema::create('bingo_off_prize_ctns_payment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prize_ctn');
            $table->string('operation', 1);
            $table->unsignedBigInteger('room');
            $table->unsignedBigInteger('provider')->nullable();
            $table->unsignedBigInteger('commerce')->nullable();
            $table->unsignedBigInteger('seller');
            $table->unsignedBigInteger('player_link');
            $table->unsignedBigInteger('sender');
            $table->unsignedBigInteger('prize_type');
            $table->unsignedBigInteger('lot');
            $table->unsignedBigInteger('ctn');
            $table->unsignedBigInteger('coin');
            $table->double('amount');
            $table->string('payment_proof', 25);
            $table->timestamps();

            $table->foreign('prize_ctn')->references('id')->on('bingo_off_prize_ctns');
            $table->foreign('room')->references('id')->on('bingo_off_rooms');
            $table->foreign('provider')->references('id')->on('providers')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('commerce')->references('id')->on('commerces')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('seller')->references('id')->on('sellers')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('player_link')->references('id')->on('player_link')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('sender')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('prize_type')->references('id')->on('game_prizes')->onDelete('cascade');
            $table->foreign('lot')->references('id')->on('bingo_off_ctns_lot');
            $table->foreign('ctn')->references('id')->on('bingo_off_ctns');
            $table->foreign('coin')->references('id')->on('coins');

            // índice compuesto
            $table->index(['room', 'sender']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bingo_off_prize_ctns_payment');
    }
};
