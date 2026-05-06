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
        Schema::create('bingo_rounds_automatic', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la ronda automática');
            $table->foreignSnowflake('bingo_room_id')
                ->unique()
                ->nullable()
                ->constrained('bingo_rooms')
                ->comment('Sala de bingo asociada');
            $table->foreignSnowflake('automatic_bingo_room_id')
                ->unique()
                ->nullable()
                ->constrained('bingo_rooms_automatic')
                ->comment('Sala automática asociada');
            $table->string('rounds', 2)->comment('Número de rondas');
            $table->json('prize_types')->comment('Tipos de premios (JSON)');
            $table->json('bingo_patterns')->comment('Patrones de bingo (JSON)');
            $table->json('prize_values')->comment('Valores de premios (JSON)');
            $table->enum('status', ['ACTIVE', 'EXECUTED'])
                ->default('ACTIVE')
                ->comment('Estado de la ronda automática');
            $table->timestamps();
            $table->index('status', 'idx_bingo_rounds_automatic_status');
            $table->index('created_at', 'idx_bingo_rounds_automatic_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_rounds_automatic');
    }
};
