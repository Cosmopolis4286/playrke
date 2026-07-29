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
        Schema::create('bingo_draws_code', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único del código de sorteo');
            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete()
                ->comment('Sala asociada');
            $table->foreignSnowflake('round_id')
                ->nullable()
                ->constrained('bingo_rounds')
                ->cascadeOnDelete()
                ->comment('Ronda asociada a esta secuencia de sorteo');
            $table->unsignedSmallInteger('num')->comment('Posición en la secuencia (1-based)');
            $table->char('sym', 6)->comment('Símbolo/código fijo');
            $table->timestamps();
            // NOTA: índices en created_at/updated_at eliminados - gestionados por Laravel

            // Evitar duplicados: un número único por sala/ronda
            $table->unique(['room_id', 'round_id', 'num'], 'unique_num_per_room_round');

            // Evitar símbolos repetidos por sala/ronda
            $table->unique(['room_id', 'round_id', 'sym'], 'unique_sym_per_room_round');

            $table->index(['room_id', 'round_id'], 'idx_bingo_draws_code_room_round');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_draws_code');
    }
};
