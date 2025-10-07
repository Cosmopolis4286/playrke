<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear tabla pivote `role_user` extendida.
     * Permite auditar asignaciones y agregar expiraciones de roles.
     */
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            // -----------------------------
            // Relaciones principales
            // -----------------------------
            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID del usuario');

            $table->foreignSnowflake('role_id')
                ->constrained('roles')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID del rol');

            // -----------------------------
            // Auditoría
            // -----------------------------
            $table->foreignSnowflake('assigned_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('ID del usuario que asignó este rol');

            // -----------------------------
            // Expiración y control
            // -----------------------------
            $table->timestamp('assigned_at')->useCurrent()->comment('Fecha y hora de asignación');
            $table->timestamp('expires_at')->nullable()->comment('Fecha de expiración del rol');

            // -----------------------------
            // Timestamps automáticos
            // -----------------------------
            $table->timestamps();

            // -----------------------------
            // Clave única
            // -----------------------------
            $table->unique(['user_id', 'role_id'], 'role_user_unique');
        });
    }

    /**
     * Eliminar tabla pivote `role_user`.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
