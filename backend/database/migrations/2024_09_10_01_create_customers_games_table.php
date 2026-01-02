<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla pivot 'customers_games'.
     *
     * Tabla pivot para la relación many-to-many entre customers y games.
     * Define qué juegos están permitidos para cada cliente.
     *
     * Relación:
     * - Customer belongsToMany Game (allowedGames)
     * - Game belongsToMany Customer
     *
     * Uso:
     * - Esta tabla determina qué juegos puede acceder cada cliente
     * - Permite control granular de acceso a juegos por cliente
     */
    public function up(): void
    {
        Schema::create('customers_games', function (Blueprint $table) {
            // ============================================
            // PRIMARY KEY
            // ============================================
            $table->snowflake('id')->primary()->comment('ID único de la relación (Snowflake)');

            // ============================================
            // FOREIGN KEYS
            // ============================================
            $table->foreignSnowflake('customer_id')
                ->constrained('customers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Cliente asociado');

            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Juego permitido');

            // ============================================
            // TIMESTAMPS
            // ============================================
            $table->timestamps();

            // ============================================
            // ÍNDICES Y CONSTRAINTS
            // ============================================
            // Evitar duplicados: un cliente no puede tener el mismo juego asignado dos veces
            $table->unique(['customer_id', 'game_id'], 'uk_customer_game');

            // Índice para búsquedas rápidas
            $table->index(['customer_id', 'game_id'], 'idx_customer_game');

            // Índice para consultas de juegos por estado (si se agrega estado en el futuro)
            $table->index('customer_id', 'idx_customer_games');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'customers_games'.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers_games');
    }
};

