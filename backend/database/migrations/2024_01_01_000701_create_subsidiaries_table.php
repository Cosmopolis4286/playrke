<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración para crear la tabla 'subsidiaries'.
     */
    public function up(): void
    {
        Schema::create('subsidiaries', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la sucursal');

            // Datos de la sucursal
            $table->string('name', 30)->comment('Nombre de la sucursal');
            $table->string('brand', 20)->nullable()->comment('Marca asociada');
            $table->string('product', 30)->nullable()->comment('Producto principal');
            $table->string('link_route', 255)->nullable()->comment('Ruta de enlace o URL');
            $table->string('token', 255)->comment('Token de autenticación');
            $table->string('security_key', 255)->comment('Llave de seguridad');

            // Comisiones y teléfono
            $table->double('commission', 8, 2)->default(0)->comment('Comisión (%) por defecto');
            $table->string('phone', 14)->nullable()->comment('Teléfono de contacto');

            // Relaciones Snowflake
            $table->foreignSnowflake('commerce_id')
                ->nullable()
                ->constrained('commerces')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Comercio asociado (opcional)');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario creador');

            $table->foreignSnowflake('country_id')
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->comment('País de la sucursal');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->comment('Moneda utilizada');

            // Estado de la sucursal
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE')
                ->comment('Estado activo/inactivo');

            // Timestamps automáticos
            $table->timestamps();

            // Índices compuestos para consultas frecuentes
            $table->index(['status', 'commerce_id', 'country_id']);
        });
    }

    /**
     * Revierte la migración eliminando la tabla 'subsidiaries'.
     */
    public function down(): void
    {
        Schema::dropIfExists('subsidiaries');
    }
};
