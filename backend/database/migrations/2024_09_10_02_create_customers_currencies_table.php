<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla pivot 'customers_currencies'.
     *
     * Tabla pivot para la relación many-to-many entre customers y currencies.
     * Permite que un cliente maneje múltiples monedas además de su moneda principal.
     *
     * Relación:
     * - Customer belongsToMany Currency (otherCurrencies)
     * - Currency belongsToMany Customer
     *
     * Uso:
     * - La moneda principal del cliente se almacena en customers.currency_id
     * - Esta tabla almacena las monedas adicionales/secundarias
     */
    public function up(): void
    {
        Schema::create('customers_currencies', function (Blueprint $table) {       
            // ============================================
            // FOREIGN KEYS
            // ============================================
            $table->foreignSnowflake('customer_id')
                ->constrained('customers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Cliente asociado');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Moneda adicional permitida');

            // Nivel de asignación
            $table->tinyInteger('level')
                ->default(1)
                ->comment('Nivel asignado: 1 = básico, 2 = intermedio, 3 = avanzado');
          
            // Estado
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE')
                ->comment('Estado del registro');

            $table->timestamps();

            // Soft deletes
            $table->softDeletes();

            // ============================================
            // ÍNDICES Y CONSTRAINTS
            // ============================================
            // Evitar duplicados: un cliente no puede tener la misma moneda adicional dos veces
            $table->unique(['customer_id', 'currency_id'], 'uk_customer_currency');

            // Índice para búsquedas rápidas
            $table->index(['customer_id', 'currency_id'], 'idx_customer_currency');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'customers_currencies'.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers_currencies');
    }
};
