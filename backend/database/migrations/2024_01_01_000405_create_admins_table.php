<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'admins'.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del administrador');

            // Relaciones Snowflake
            $table->foreignSnowflake('country_id')
                ->nullable()
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('ID del país del administrador');

            $table->foreignSnowflake('currency_id')
                ->nullable()
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('ID de la moneda del administrador');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('ID del usuario administrador');

            $table->foreignSnowflake('unique_code_id')
                ->constrained('unique_codes')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Código único de referencia');

            // Estado del administrador
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED', 'DELETED'])
                ->default('ACTIVE')
                ->comment('Estados del administrador');

            // Permisos en formato JSON
            $table->json('permissions')
                ->nullable()
                ->comment('Permisos específicos del administrador');

            // Último login
            $table->timestamp('last_login_at')
                ->nullable()
                ->comment('Fecha del último inicio de sesión');

            // Timestamps automáticos
            $table->timestamps();

            // Soft deletes
            $table->softDeletes();

            // Índices compuestos para consultas frecuentes
            $table->index('user_id');
            $table->index(['country_id', 'status']);
            $table->index(['status', 'last_login_at']);
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'admins'.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
