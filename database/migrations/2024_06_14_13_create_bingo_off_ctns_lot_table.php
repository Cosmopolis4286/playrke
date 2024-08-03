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
        Schema::create('bingo_off_ctns_lot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room');
            $table->unsignedBigInteger('commerce');
            $table->unsignedBigInteger('seller');
            $table->unsignedBigInteger('player_link');
            $table->unsignedBigInteger('coin');
            $table->double('exchange_rate');
            $table->integer('ctns');
            $table->double('sale_seller_ctns');
            $table->boolean('status')->default(0);
            $table->timestamps();

            $table->foreign('coin')->references('id')->on('coins');
            $table->foreign('room')->references('id')->on('bingo_off_rooms');
            $table->foreign('commerce')->references('id')->on('commerces');
            $table->foreign('seller')->references('id')->on('sellers');
            $table->foreign('player_link')->references('id')->on('player_link');

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
        Schema::dropIfExists('bingo_off_ctns_lot');
    }
};
