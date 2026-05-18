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
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único del sorteo');
            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete()
                ->comment('Sala asociada');
            $table->foreignSnowflake('round_id')
                ->constrained('bingo_rounds')
                ->cascadeOnDelete()
                ->comment('Ronda asociada');
            $table->char('chip', 2)->comment('Ficha extraída (B, I, N, G, O + número)');
            $table->char('code', 8)->comment('Código asociado a la ficha');
            $table->integer('time')->comment('Tiempo en segundos desde el inicio de la ronda');
            $table->enum('status', ['WAITING', 'FINISHED'])->default('WAITING')->comment('Estado del sorteo');
            $table->timestamps();

            // Índice único: chip no puede repetirse en la misma sala/ronda
            $table->unique(['room_id', 'round_id', 'chip'], 'unique_chip_per_room_round');
            $table->index(['room_id', 'round_id'], 'idx_bingo_draws_room_round');
            $table->index(['room_id', 'round_id', 'status'], 'idx_bingo_draws_room_round_status');
            // NOTA: índices en created_at/updated_at eliminados - gestionados por Laravel
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
