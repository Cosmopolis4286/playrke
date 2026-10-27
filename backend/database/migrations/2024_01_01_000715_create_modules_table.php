<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Módulos raíz del sidebar (ver SidebarMenuService::$menuStructure) como
 * feature flags globales: apagar un módulo aquí lo oculta para TODOS los
 * usuarios, incluido superadmin — a diferencia de Permission, que controla
 * acceso por rol/usuario, esto es un interruptor de disponibilidad del
 * sistema completo (mantenimiento, lanzamiento por etapas).
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('modules');
    }

    public function up() : void
    {
        Schema::create('modules', function(Blueprint $table) {
            $table->string('id', 20)->primary();
            // key coincide con la clave raíz de SidebarMenuService::$menuStructure
            // (ej. 'games_bingo', 'wallet') — es el punto de unión entre esta
            // tabla y el árbol de menú definido en PHP.
            $table->string('key', 100)->unique();
            $table->string('label', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
