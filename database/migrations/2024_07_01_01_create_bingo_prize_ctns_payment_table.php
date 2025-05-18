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
        Schema::create('bingo_prize_ctns_payment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prize_ctn');
            $table->enum('payment', ['CS', 'CC', 'SP']);
            $table->string('description', 35)->nullable();
            $table->unsignedBigInteger('room');
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('seller');
            $table->foreignId('player_link')->constrained('player_links')->onDelete('cascade');
            $table->unsignedBigInteger('sender');
            $table->unsignedBigInteger('prize_type');
            $table->unsignedBigInteger('lot');
            $table->unsignedBigInteger('ctn');
            $table->unsignedBigInteger('coin');
            $table->double('amount');
            $table->string('payment_proof', 25);
            $table->timestamps();

            $table->foreign('prize_ctn')->references('id')->on('bingo_prize_ctns');
            $table->foreign('room')->references('id')->on('bingo_rooms');
            $table->foreign('seller')->references('id')->on('sellers')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('room_user')->references('id')->on('bingo_rooms_user')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('sender')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('prize_type')->references('id')->on('game_prizes')->onDelete('cascade');
            $table->foreign('lot')->references('id')->on('bingo_ctns_lot');
            $table->foreign('ctn')->references('id')->on('bingo_ctns');
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
        Schema::dropIfExists('bingo_prize_ctns_payment');
    }
};
