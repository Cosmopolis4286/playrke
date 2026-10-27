<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down() : void
    {
        Schema::dropIfExists('bingo_rooms_winners');
    }

    /**
     * Run the migrations.
     */
    public function up() : void
    {
        Schema::create('bingo_rooms_winners', function(Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único del ganador');
            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete()
                ->comment('Sala asociada');
            $table->foreignSnowflake('room_user_id')
                ->constrained('bingo_room_users')
                ->cascadeOnDelete()
                ->comment('Usuario en la sala');
            $table->foreignSnowflake('round_id')
                ->constrained('bingo_rounds')
                ->comment('Ronda ganada');
            $table->enum('award_status', ['WAITING', 'PAID'])
                ->default('WAITING')
                ->comment('Estado del pago del premio');
            $table->timestamps();
            $table->index(['room_id', 'award_status'], 'idx_bingo_rooms_winners_room_status');
            // NOTA: índices en created_at/updated_at eliminados - gestionados por Laravel

            // Falta (round_id, award_status) y room_user_id (usados en BingoRoomWinnerRepository).
            $table->index(['round_id', 'award_status'], 'idx_bingo_rooms_winners_round_status');
            $table->index('room_user_id', 'idx_bingo_rooms_winners_room_user');
        });
    }
};
