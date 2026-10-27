<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revertir la migración: eliminar tabla 'customers'.
     */
    public function down() : void
    {
        Schema::dropIfExists('customers');
    }

    /**
     * Ejecutar la migración: crear tabla 'customers'.
     *
     * Tabla principal para almacenar información de clientes que se conectan
     * al sistema mediante API. Cada cliente tiene credenciales únicas y puede
     * manejar múltiples monedas y juegos.
     *
     * Relaciones:
     * - BelongsTo: Country, Currency (principal), User
     * - BelongsToMany: Currency (otras monedas via customers_currencies)
     * - BelongsToMany: Game (juegos permitidos via customers_games)
     */
    public function up() : void
    {
        Schema::create('customers', function(Blueprint $table) {
            // ============================================
            // PRIMARY KEY
            // ============================================
            $table->snowflake('id')->primary()->comment('ID único del cliente (Snowflake)');

            // ============================================
            // INFORMACIÓN BÁSICA DEL CLIENTE
            // ============================================
            $table->string('name', 255)->comment('Nombre del cliente');
            $table->string('business_name', 255)->nullable()->comment('Razón social del cliente');
            $table->decimal('commission', 5, 2)->nullable()->comment('Comisión (%) por defecto');

            // ============================================
            // DATOS DE CONTACTO
            // ============================================
            $table->string('phone', 20)->unique()->comment('Teléfono de contacto');

            // ============================================
            // CONFIGURACIÓN API
            // ============================================
            $table->string('api_route', 255)->comment('Ruta API del cliente');
            $table->string('customer_key', 255)->unique()->comment('Llave de conexión única del cliente');

            // ============================================
            // MULTIMEDIA
            // ============================================
            $table->string('logo', 255)->nullable()->comment('Ruta del logo del cliente');

            // ============================================
            // FOREIGN KEYS
            // ============================================
            $table->foreignSnowflake('country_id')
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('País del cliente');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Moneda principal del cliente');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario asociado al cliente');

            // ============================================
            // ESTADO
            // ============================================
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED', 'DELETED'])
                ->default('ACTIVE')
                ->index()
                ->comment('Estado del cliente');

            // ============================================
            // TIMESTAMPS
            // ============================================
            $table->timestamps();

            // Soft deletes
            $table->softDeletes();

            // ============================================
            // ÍNDICES COMPUESTOS
            // ============================================
            $table->index(['status', 'country_id'], 'idx_customer_status_country');
            $table->index(['user_id', 'status'], 'idx_customer_user_status');
        });
    }
};
