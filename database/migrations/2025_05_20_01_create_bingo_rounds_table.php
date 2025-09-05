<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_rounds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete();

            $table->tinyInteger('round')->comment('Número de la ronda');
            $table->timestamp('start_time')->nullable()->comment('Inicio de la ronda');
            $table->char('last_chip', 2)->nullable()->comment('Última ficha jugada');

            $table->enum('prize_type', ['PCT', 'USD', 'OTHER'])->comment('Tipo de premio');
            $table->foreignId('play_type_id')
                ->constrained('bingo_patterns')
                ->cascadeOnDelete();

            $table->decimal('prize_value', 15, 6)->nullable()->comment('Valor del premio');
            $table->enum('status', ['ACTIVE', 'IN_PROGRESS', 'FINISHED'])
                ->default('ACTIVE')
                ->index();

            $table->timestamps();

            // Índice compuesto útil para evitar duplicados de ronda por sala
            $table->unique(['room_id', 'round'], 'unique_round_per_room');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_rounds');
    }
};
