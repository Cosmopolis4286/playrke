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
        Schema::create('bingo_card_patterns', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la relación cartón-patrón');

            $table->foreignSnowflake('card_id')
                ->constrained('bingo_cards')
                ->cascadeOnDelete()
                ->comment('Cartón asociado');

            $table->foreignSnowflake('bingo_pattern_id')
                ->constrained('bingo_patterns')
                ->cascadeOnDelete()
                ->comment('Patrón de bingo validado');

            $table->foreignSnowflake('round_id')
                ->nullable()
                ->constrained('bingo_rounds')
                ->nullOnDelete()
                ->comment('Ronda en la que se validó el patrón');

            $table->enum('status', ['PENDING', 'VALIDATED', 'REJECTED'])
                ->default('PENDING')
                ->comment('Estado de validación del patrón');

            $table->timestamp('validated_at')
                ->nullable()
                ->comment('Fecha y hora de validación');

            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index('card_id', 'idx_bingo_card_patterns_card');
            $table->index('bingo_pattern_id', 'idx_bingo_card_patterns_pattern');
            $table->index('round_id', 'idx_bingo_card_patterns_round');
            $table->index(['card_id', 'status'], 'idx_bingo_card_patterns_card_status');
            $table->index(['status', 'validated_at'], 'idx_bingo_card_patterns_status_validated');
            $table->index('created_at', 'idx_bingo_card_patterns_created');

            // Evita duplicados de patrón por cartón
            $table->unique(['card_id', 'bingo_pattern_id', 'round_id'], 'bingo_card_pattern_round_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_card_patterns');
    }
};
