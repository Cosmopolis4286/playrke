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
        Schema::create('game_mode_relationship', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game');
            $table->unsignedBigInteger('mode');
            $table->boolean('status')->default(0);
            $table->timestamps();

            $table->foreign('game')->references('id')->on('games')->onUpdate('cascade');
            $table->foreign('mode')->references('id')->on('game_modes')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_mode_relationship');
    }
};
