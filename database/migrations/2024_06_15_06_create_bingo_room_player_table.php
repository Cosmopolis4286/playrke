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
        Schema::create('bingo_rooms_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room');
            $table->unsignedBigInteger('provider')->nullable();
            $table->unsignedBigInteger('commerce')->nullable();
            $table->unsignedBigInteger('player');
            $table->string('winner', 1)->default(0);
            $table->timestamps();

            $table->foreign('room')->references('id')->on('bingo_rooms');
            $table->foreign('provider')->references('id')->on('providers')->onUpdate('cascade');
            $table->foreign('commerce')->references('id')->on('commerces');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bingo_rooms_user');
    }
};
