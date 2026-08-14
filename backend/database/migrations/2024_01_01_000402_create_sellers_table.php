<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'sellers'.
     */
    public function up(): void
    {
        Schema::create('sellers', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del vendedor');

            // Datos del vendedor
            $table->string('name', 30)->comment('Nombre del vendedor');
            $table->string('phone', 20)->unique()->comment('Teléfono de contacto');
            $table->decimal('commission', 5, 2)->default(0.00)->comment('Comisión (%)');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('ID de usuario del vendedor');

            // Relaciones Snowflake
            $table->foreignSnowflake('country_id')
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->comment('País del vendedor');

            $table->foreignSnowflake('manager_user_id')
                ->constrained('users')->cascadeOnDelete()
                ->comment('Usuario principal al que representa'); // Usuario al que vende

            // Sucursal/agencia a la que pertenece el vendedor (jerarquía real
            // Comercializadora → Agencia → Taquilla). Nullable: un seller
            // puede reportar directo al Commerce sin agencia intermedia.
            // Cuando está seteado, es la fuente de verdad del Commerce real
            // del seller (ver Seller::resolveCommerce()) — manager_user_id
            // sigue siendo NOT NULL por compatibilidad histórica, pero deja
            // de ser confiable por sí solo cuando subsidiary_id está presente.
            $table->foreignSnowflake('subsidiary_id')
                ->nullable()
                ->constrained('subsidiaries')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Sucursal/agencia a la que pertenece (opcional)');

            // Estado del vendedor
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE')
                ->comment('Estado activo/inactivo');

            // Timestamps automáticos
            $table->timestamps();

            // Soft deletes
            $table->softDeletes();

            // Índices compuestos para consultas frecuentes
            $table->index(['status', 'manager_user_id']);
            $table->index('manager_user_id');
            $table->index(['country_id']);
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'sellers'.
     */
    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
