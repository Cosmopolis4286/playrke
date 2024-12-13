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
            $table->unsignedBigInteger('customer')->nullable();
            $table->unsignedBigInteger('commerce')->nullable();
            $table->unsignedBigInteger('subsidiary')->nullable();
            $table->unsignedBigInteger('seller')->nullable();
            $table->integer('player_user')->nullable();
            $table->unsignedBigInteger('player_link')->nullable();
            $table->string('ctns', 3);
            $table->double('automarker')->nullable();
            $table->string('winner', 1)->default('0');
            $table->timestamps();

            // Definición de claves foráneas
            $table->foreign('room')->references('id')->on('bingo_rooms');
            $table->foreign('customer')->references('id')->on('customers');
            $table->foreign('commerce')->references('id')->on('commerces');
            $table->foreign('seller')->references('id')->on('sellers');
            $table->foreign('subsidiary')->references('id')->on('subsidiaries')->onUpdate('cascade');
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
        Schema::dropIfExists('bingo_rooms_user');
    }
};
