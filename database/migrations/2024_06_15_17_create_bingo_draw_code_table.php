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
        Schema::create('bingo_draw_code', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room');
            $table->string('num', 1);
            $table->string('sym', 6);
            $table->timestamps();

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
        Schema::dropIfExists('bingo_draw_code');
    }
};
