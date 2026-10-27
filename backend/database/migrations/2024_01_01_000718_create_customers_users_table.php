<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revertir la migración: eliminar tabla 'customers_users'.
     */
    public function down() : void
    {
        Schema::dropIfExists('customers_users');
    }

    /**
     * Ejecutar la migración: crear tabla 'customers_users'.
     */
    public function up() : void
    {
        Schema::create('customers_users', function(Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la relación cliente-usuario');

            // Relaciones
            $table->foreignSnowflake('customer_id')
                ->constrained('customers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Cliente asociado');

            $table->string('user_id', 36)->comment('ID del usuario externo o UUID');

            // Información adicional
            $table->string('brand', 30)->nullable()->comment('Marca asociada a este usuario');
            $table->string('token', 255)->comment('Token de autenticación del usuario');
            $table->string('country_code', 3)->nullable()->comment('Código ISO del país del usuario');
            $table->string('currency_id', 20)->comment('Código de moneda (ISO o interno)');

            // Actividad
            $table->timestamp('last_activity')->nullable()->comment('Última actividad del usuario');

            // Timestamps automáticos
            $table->timestamps();

            // Índices compuestos para búsquedas rápidas
            $table->index(['customer_id', 'user_id'], 'idx_customer_user');
            $table->index(['country_code', 'currency_id'], 'idx_country_currency');
        });
    }
};
