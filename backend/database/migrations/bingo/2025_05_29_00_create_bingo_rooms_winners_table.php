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
        Schema::create('bingo_rooms_winners', function (Blueprint $table) {
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
            $table->index('created_at', 'idx_bingo_rooms_winners_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_rooms_winners');
    }
};
