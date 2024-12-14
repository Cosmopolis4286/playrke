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
            $table->integer('player_user')->nullable();
            $table->unsignedBigInteger('player_link')->nullable();
            $table->string('ctns', 3);
            $table->double('automarker')->nullable();
            $table->string('winner', 1)->default('0');
            $table->timestamps();

            // Definición de claves foráneas
            $table->foreign('room')->references('id')->on('bingo_rooms');
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
