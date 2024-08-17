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
        Schema::create('bingo_ctns_payment', function (Blueprint $table) {
            $table->id();
            $table->string('operation', 1);
            $table->unsignedBigInteger('room');
            $table->unsignedBigInteger('commerce');
            $table->unsignedBigInteger('seller');
            $table->unsignedBigInteger('player_link');
            $table->string('payment_type', 1);
            $table->unsignedBigInteger('coin');
            $table->unsignedBigInteger('lot');
            $table->double('amount');
            $table->string('payment_proof', 25);
            $table->timestamps();

            $table->foreign('room')->references('id')->on('bingo_rooms');
            $table->foreign('commerce')->references('id')->on('commerces');
            $table->foreign('seller')->references('id')->on('sellers')->onDelete('cascade');
            $table->foreign('player_link')->references('id')->on('player_link');
            $table->foreign('coin')->references('id')->on('coins');
            $table->foreign('lot')->references('id')->on('bingo_ctns_lot');

            // índice compuesto
            $table->index(['room', 'commerce', 'seller']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bingo_ctns_payment');
    }
};
