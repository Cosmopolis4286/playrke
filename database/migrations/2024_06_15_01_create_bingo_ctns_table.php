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
        Schema::create('bingo_ctns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room');
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('seller')->nullable();
            $table->integer('player_user')->nullable();
            $table->unsignedBigInteger('room_user');
            $table->unsignedBigInteger('lot');
            $table->string('coin', 15);
            $table->string('serial', 20);
            $table->string('b1', 2);
            $table->string('b2', 2);
            $table->string('b3', 2);
            $table->string('b4', 2);
            $table->string('b5', 2);
            $table->string('i1', 2);
            $table->string('i2', 2);
            $table->string('i3', 2);
            $table->string('i4', 2);
            $table->string('i5', 2);
            $table->string('n1', 2);
            $table->string('n2', 2);
            $table->string('n3', 2)->nullable();
            $table->string('n4', 2);
            $table->string('n5', 2);
            $table->string('g1', 2);
            $table->string('g2', 2);
            $table->string('g3', 2);
            $table->string('g4', 2);
            $table->string('g5', 2);
            $table->string('o1', 2);
            $table->string('o2', 2);
            $table->string('o3', 2);
            $table->string('o4', 2);
            $table->string('o5', 2);
            $table->string('hash', 64);
            $table->timestamps();

            $table->foreign('room')->references('id')->on('bingo_rooms');
            $table->foreign('seller')->references('id')->on('sellers');
            $table->foreign('room_user')->references('id')->on('bingo_rooms_user');
            $table->foreign('lot')->references('id')->on('bingo_ctns_lot');

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
        Schema::dropIfExists('bingo_ctns');
    }
};
