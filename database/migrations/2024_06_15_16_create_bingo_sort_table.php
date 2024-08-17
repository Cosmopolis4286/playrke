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
        Schema::create('bingo_sort', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room');
            $table->unsignedTinyInteger('chip');
            $table->string('code', 8);
            $table->integer('time');
            $table->boolean('win')->default(0);
            $table->boolean('status')->default(0);
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
        Schema::dropIfExists('bingo_sort');
    }
};
