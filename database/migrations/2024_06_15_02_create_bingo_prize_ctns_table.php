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
        Schema::create('bingo_prize_ctns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room');
            $table->enum('active', ['Y', 'N'])->default('N');
            $table->unsignedBigInteger('bingo_creator_id');
            $table->unsignedBigInteger('seller')->nullable();
            $table->unsignedBigInteger('room_user');
            $table->integer('player_user')->nullable();
            $table->unsignedBigInteger('player_link')->nullable();
            $table->unsignedBigInteger('lot');
            $table->unsignedBigInteger('ctn');
            $table->unsignedBigInteger('prize_type');
            $table->string('coin', 15);
            $table->double('amount');
            $table->boolean('status')->default(0);
            $table->timestamps();

            $table->foreign('room')->references('id')->on('bingo_rooms');
            $table->foreign('bingo_creator_id')->references('id')->on('bingo_creator');
            $table->foreign('seller')->references('id')->on('sellers');
            $table->foreign('player_link')->references('id')->on('player_link');
            $table->foreign('room_user')->references('id')->on('bingo_rooms_user');
            $table->foreign('lot')->references('id')->on('bingo_ctns_lot');
            $table->foreign('ctn')->references('id')->on('bingo_ctns');
            $table->foreign('prize_type')->references('id')->on('game_prizes')->onUpdate('cascade');

            // Índice compuesto
            $table->index(['room']);
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bingo_prize_ctns');
    }
};
