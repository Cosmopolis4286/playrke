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
        Schema::create('bingo_draw', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room')->constrained('bingo_rooms')->onDelete('cascade');
            $table->string('chip', 2);
            $table->string('code', 8);
            $table->integer('time');
            $table->string('status')->default('0');
            $table->timestamps();

            // Índice único para evitar duplicados de chip por sala
            $table->unique(['room', 'chip'], 'unique_chip_per_room');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bingo_draw');
    }
};
