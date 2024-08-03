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
        Schema::create('bingo_off_rooms_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room');
            $table->unsignedBigInteger('commerce');
            $table->unsignedBigInteger('seller');
            $table->unsignedBigInteger('player_link');
            $table->string('winner', 1)->default(0);
            $table->timestamps();

            $table->foreign('room')->references('id')->on('bingo_off_rooms');
            $table->foreign('commerce')->references('id')->on('commerces');
            $table->foreign('seller')->references('id')->on('sellers');
            $table->foreign('player_link')->references('id')->on('player_link');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bingo_off_rooms_user');
    }
};
