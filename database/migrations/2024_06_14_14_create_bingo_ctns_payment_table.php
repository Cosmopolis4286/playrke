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
            $table->enum('payment', ['PS', 'SC']);
            $table->string('description', 35)->nullable();
            $table->unsignedBigInteger('room');
            $table->foreignId('creator_user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('seller')->nullable();
            $table->integer('user')->nullable();
            $table->foreignId('external_player_id')->constrained('external_players')->onDelete('cascade');
            $table->unsignedBigInteger('coin');
            $table->unsignedBigInteger('lot');
            $table->double('amount');
            $table->string('payment_proof', 25);
            $table->timestamps();

            $table->foreign('room')->references('id')->on('bingo_rooms');
            $table->foreign('seller')->references('id')->on('sellers')->onDelete('cascade');
            $table->foreign('coin')->references('id')->on('coins');
            $table->foreign('lot')->references('id')->on('bingo_ctns_lot');

            // índice compuesto
            $table->index(['room', 'seller']);
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
