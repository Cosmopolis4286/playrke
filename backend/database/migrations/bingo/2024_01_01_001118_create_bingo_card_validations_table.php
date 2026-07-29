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
        Schema::create('bingo_card_validations', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la validación');

            $table->foreignSnowflake('card_id')
                ->constrained('bingo_cards')
                ->cascadeOnDelete()
                ->comment('Cartón validado');

            $table->foreignSnowflake('round_id')
                ->constrained('bingo_rounds')
                ->cascadeOnDelete()
                ->comment('Ronda en la que se validó');

            $table->foreignSnowflake('pattern_id')
                ->constrained('bingo_patterns')
                ->cascadeOnDelete()
                ->comment('Patrón validado');

            $table->foreignSnowflake('validated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que realizó la validación (sistema o administrador)');

            $table->enum('validation_type', ['AUTOMATIC', 'MANUAL', 'SYSTEM'])
                ->default('AUTOMATIC')
                ->comment('Tipo de validación: AUTOMATIC = sistema, MANUAL = manual, SYSTEM = validación del sistema');

            $table->enum('status', ['PENDING', 'VALIDATED', 'REJECTED', 'CANCELLED'])
                ->default('PENDING')
                ->comment('Estado de la validación');

            $table->text('validation_notes')
                ->nullable()
                ->comment('Notas de la validación');

            $table->timestamp('validated_at')
                ->nullable()
                ->comment('Fecha y hora de validación');

            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index('card_id', 'idx_bingo_card_validations_card');
            $table->index('round_id', 'idx_bingo_card_validations_round');
            $table->index('pattern_id', 'idx_bingo_card_validations_pattern');
            $table->index(['card_id', 'status'], 'idx_bingo_card_validations_card_status');
            $table->index(['round_id', 'status'], 'idx_bingo_card_validations_round_status');
            $table->index(['validation_type', 'status'], 'idx_bingo_card_validations_type_status');
            $table->index('validated_at', 'idx_bingo_card_validations_validated');
            // NOTA: índices en created_at/updated_at eliminados - gestionados por Laravel

            // Evita duplicados: misma combinación de cartón, ronda y patrón
            $table->unique(['card_id', 'round_id', 'pattern_id'], 'unique_card_round_pattern_validation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_card_validations');
    }
};
