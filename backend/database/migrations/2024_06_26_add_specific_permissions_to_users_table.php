<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: agregar permisos específicos a usuarios
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Campo para permisos específicos del usuario (sobreescribe permisos de rol)
            $table->json('specific_permissions')->nullable()->comment('Permisos específicos del usuario');
            
            // Índice para búsqueda rápida
            $table->index('specific_permissions', 'idx_specific_permissions');
        });
    }

    /**
     * Revertir la migración: eliminar permisos específicos
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_specific_permissions');
            $table->dropColumn('specific_permissions');
        });
    }
};
