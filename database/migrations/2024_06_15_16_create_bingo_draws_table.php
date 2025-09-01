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
            $table->foreignId('room_id')->constrained('bingo_rooms')->onDelete('cascade');
            $table->tinyInteger('round')->default(1); // número de ronda
            $table->char('chip', 2); // ficha extraída
            $table->char('code', 8); // código asociado
            $table->unsignedInteger('time'); // segundos o timestamp relativo
            $table->enum('status', ['0', '1'])->default('0'); // pendiente/procesado
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
