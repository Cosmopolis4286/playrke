<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear tabla pivote role_user sin ID, solo relaciones y timestamps.
     */
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            // Relación con usuario
            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID del usuario');

            // Relación con rol
            $table->foreignSnowflake('role_id')
                ->constrained('roles')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID del rol');

            $table->timestamps();

            // Evitar duplicados
            $table->unique(['user_id', 'role_id']);
        });
    }

    /**
     * Eliminar tabla pivote
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
