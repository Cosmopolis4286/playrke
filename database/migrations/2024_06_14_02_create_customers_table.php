<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'customers'.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del cliente');

            // Datos básicos
            $table->string('name', 30)->comment('Nombre del cliente');
            $table->string('description', 45)->nullable()->comment('Descripción opcional');
            $table->string('brand', 30)->nullable()->comment('Marca asociada');
            $table->string('product', 30)->nullable()->comment('Producto principal');
            $table->string('logo', 35)->nullable()->comment('Logo o imagen');
            $table->string('link_route', 255)->nullable()->comment('Ruta de enlace o URL');

            // Relaciones Snowflake
            $table->foreignSnowflake('country_id')
                ->nullable()
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('País del cliente');

            $table->foreignSnowflake('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Usuario responsable');

            // Claves y códigos
            $table->string('customer_code', 30)->nullable()->unique()->comment('Código único del cliente');
            $table->string('token', 36)->nullable()->unique()->comment('Token de autenticación');
            $table->text('customer_key')->nullable()->comment('Llave del cliente');
            $table->text('security_key')->nullable()->comment('Llave de seguridad');

            // Contacto y finanzas
            $table->string('phone', 12)->nullable()->comment('Teléfono de contacto');
            $table->decimal('commission', 5, 2)->nullable()->comment('Comisión (%) asociada');
            $table->boolean('all_in')->default(false)->comment('Indica si aplica “All In”');

            // Estado del cliente
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE')
                ->comment('Estado activo/inactivo');

            // Timestamps automáticos
            $table->timestamps();

            // Índices compuestos para consultas frecuentes
            $table->index(['status', 'country_id', 'user_id']);
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'customers'.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
