<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla pivot 'role_user'.
     */
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            // ID Snowflake para la tabla pivot (opcional, pero útil para referencia futura)
            $table->snowflake('id')->primary()->comment('ID único de la relación user-role');

            // Relación con usuario usando Snowflake
            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID del usuario');

            // Relación con rol usando Snowflake
            $table->foreignSnowflake('role_id')
                ->constrained('roles')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID del rol');

            // Timestamps automáticos
            $table->timestamps();

            // Constraint único para evitar duplicados
            $table->unique(['user_id', 'role_id']);
        });
    }

    /**
     * Revertir la migración: eliminar tabla pivot 'role_user'.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
