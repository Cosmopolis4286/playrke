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
        Schema::create('commerces_game_permission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commerce')->nullable();
            $table->unsignedBigInteger('game');
            $table->unsignedBigInteger('game_mode');
            $table->boolean('public_rooms')->default(0);
            $table->boolean('status')->default(0);
            $table->timestamps();

            $table->foreign('commerce')->references('id')->on('commerces');
            $table->foreign('game')->references('id')->on('games');
            $table->foreign('game_mode')->references('id')->on('game_modes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commerces_game_permission');
    }
};
