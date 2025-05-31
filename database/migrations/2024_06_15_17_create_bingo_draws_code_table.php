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
        Schema::create('bingo_draws_code', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room');
            $table->string('num', 1);
            $table->string('sym', 6);
            $table->timestamps();

            $table->foreign('room')->references('id')->on('bingo_rooms');

            // Evitar duplicados: un número único por sala
            $table->unique(['room', 'num'], 'unique_num_per_room');

            // Opcional: evitar que se repita un símbolo por sala (si aplica en tu lógica)
            // $table->unique(['room', 'sym'], 'unique_sym_per_room');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bingo_draws_code');
    }
};
