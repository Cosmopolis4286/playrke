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
        Schema::create('sellers_room_limits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game');
            $table->integer('room');
            $table->unsignedBigInteger('seller');
            $table->double('commn');
            $table->timestamps();

            $table->foreign('game')->references('id')->on('games');
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
        Schema::dropIfExists('sellers_room_limits');
    }
};
