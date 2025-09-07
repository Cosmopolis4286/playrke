<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bingo_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignSnowflake('room_id')->constrained('bingo_rooms')->onDelete('cascade');
            $table->string('round', 2)->default('1'); // número de ronda
            $table->char('chip', 2); // ficha extraída
            $table->char('code', 8); // código asociado
            $table->integer('time'); // segundos o timestamp relativo
            $table->enum('status', ['WAITING', 'FINISHED'])->default('WAITING'); // pendiente/finalizado
            $table->timestamps();

            // Índice único: chip no puede repetirse en la misma sala/ronda
            $table->unique(['room_id', 'round', 'chip'], 'unique_chip_per_room_round');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_draws');
    }
};
