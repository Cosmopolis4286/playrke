<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eliminar tabla pivote `role_user`.
     */
    public function down() : void
    {
        Schema::dropIfExists('role_user');
    }

    /**
     * Crear tabla pivote `role_user` extendida.
     * Permite auditar asignaciones y agregar expiraciones de roles.
     */
    public function up() : void
    {
        Schema::create('role_user', function(Blueprint $table) {
            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignSnowflake('role_id')
                ->constrained('roles')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignSnowflake('assigned_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->enum('status', ['ACTIVE', 'INACTIVE'])
                ->default('ACTIVE')->index();

            $table->timestamp('assigned_at')
                ->useCurrent();

            $table->timestamp('expires_at')
                ->nullable()
                ->index();

            $table->timestamps();

            $table->unique(['user_id', 'role_id'], 'role_user_unique');

            $table->index(
                ['user_id', 'status', 'expires_at'],
                'role_user_active_idx',
            );

            $table->index(
                ['role_id', 'status'],
                'role_user_role_active_idx',
            );
        });
    }
};
