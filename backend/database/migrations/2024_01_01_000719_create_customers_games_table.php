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
     *
     * Campos de integración (customer_game_id, product, description): usados
     * por SeamlessWallet::transferMoney() para armar el payload "Game"/
     * "Product" que el proveedor externo requiere en TransferMoney.
     */
    public function up(): void
    {
        Schema::create('customers_games', function (Blueprint $table) {
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

            // Identificador del juego en el sistema del proveedor externo
            // (distinto del game_id interno) — único por customer, ya que
            // cada uno define sus propios códigos.
            $table->string('customer_game_id', 100)->nullable()->comment('ID del juego en el sistema del proveedor externo');
            $table->string('product', 100)->nullable()->comment('Producto asociado en el proveedor externo');
            $table->string('description', 255)->nullable()->comment('Descripción del juego para el proveedor externo');

            // Estado
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE')
                ->comment('Estado del registro');

            // Timestamps automáticos
            $table->timestamps();
            // Soft deletes
            $table->softDeletes();

            // ============================================
            // ÍNDICES Y CONSTRAINTS
            // ============================================
            // Evitar duplicados: un cliente no puede tener el mismo juego asignado dos veces
            $table->unique(['customer_id', 'game_id'], 'uk_customer_game');

            // Evitar duplicados de código de proveedor externo por cliente
            $table->unique(['customer_id', 'customer_game_id'], 'uk_customer_customer_game_id');

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
