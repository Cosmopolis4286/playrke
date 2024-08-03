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
        Schema::create('game_extras', function (Blueprint $table) {
            $table->id();
            $table->string('description', 20);
            $table->double('price', 2, 2);
            $table->unsignedBigInteger('game');
            $table->boolean('status')->default(0);
            $table->timestamps();

            $table->foreign('game')->references('id')->on('games');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_extras');
    }
};
