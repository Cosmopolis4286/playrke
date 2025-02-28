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
        Schema::create('bingo_ctns_lot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room');
            $table->foreignId('user_creator')->constrained('users')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('seller')->nullable();
            $table->unsignedBigInteger('room_user');
            $table->integer('player_user')->nullable();
            $table->unsignedBigInteger('player_link')->nullable();
            $table->double('ctn_price');
            $table->double('exchange_rate');
            $table->integer('ctns');
            $table->string('coin', 15);
            $table->double('sale_amount');
            $table->double('final_sale_amount');
            $table->boolean('status')->default(0);
            $table->timestamps();

            $table->foreign('room')->references('id')->on('bingo_rooms');
            $table->foreign('seller')->references('id')->on('sellers');
            $table->foreign('room_user')->references('id')->on('bingo_rooms_user');
            $table->foreign('player_link')->references('id')->on('player_link');

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
        Schema::dropIfExists('bingo_ctns_lot');
    }
};
