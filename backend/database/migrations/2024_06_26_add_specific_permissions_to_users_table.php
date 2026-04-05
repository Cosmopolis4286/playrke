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
            
            // No crear índice directo en JSON (MySQL no lo permite)
            // Se puede agregar índice virtual si se necesita más adelante
        });
    }

    /**
     * Revertir la migración: eliminar permisos específicos
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar campo de permisos específicos
            $table->dropColumn('specific_permissions');
        });
    }
};
