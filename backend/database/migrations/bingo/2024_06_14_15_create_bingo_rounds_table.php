<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_rounds', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la ronda');

            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete()
                ->comment('Sala asociada');

            $table->foreignSnowflake('room_config_id')
                ->constrained('bingo_rooms_config')
                ->cascadeOnDelete()
                ->comment('Configuración específica de la sala');

            $table->tinyInteger('round')->unsigned()->comment('Número de la ronda');
            $table->timestamp('start_time')->nullable()->comment('Inicio de la ronda');
            $table->char('last_chip', 2)->nullable()->comment('Última ficha jugada');

            $table->enum('prize_type', ['PCT', 'FIXED', 'OTHER'])->comment('Tipo de premio');

            $table->foreignSnowflake('bingo_pattern_id')
                ->constrained('bingo_patterns')
                ->cascadeOnDelete()
                ->comment('Patrón de bingo asociado');

            $table->enum('status', ['ACTIVE', 'IN_PROGRESS', 'FINISHED'])
                ->default('ACTIVE')
                ->index();

            $table->timestamps();
            // NOTA: índices en created_at/updated_at eliminados - gestionados por Laravel

            // Índices para consultas frecuentes
            $table->index('room_config_id', 'idx_bingo_rounds_room_config');

            // Índice compuesto útil para evitar duplicados de ronda por sala
            $table->unique(['room_id', 'round'], 'unique_round_per_room');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_rounds');
    }
};
