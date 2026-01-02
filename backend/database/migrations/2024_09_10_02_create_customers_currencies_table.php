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

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Moneda adicional permitida');

            // ============================================
            // TIMESTAMPS
            // ============================================
            $table->timestamps();

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
