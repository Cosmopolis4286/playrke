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
        Schema::create('game_jackpot_type', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game');
            $table->unsignedBigInteger('game_mode');
            $table->unsignedBigInteger('jackpot_type')->default(1);
            $table->boolean('status')->default(1);
            $table->timestamps();

            $table->foreign('game')->references('id')->on('games')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('game_mode')->references('id')->on('game_modes')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('jackpot_type')->references('id')->on('jackpot_type')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_jackpot_type');
    }
};
