<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visibilidad por rol de items del sidebar (padres y submenús de
 * SidebarMenuService::$menuStructure). Reemplaza el array hardcodeado
 * `exclude_roles` como mecanismo de decisión — no es una capa adicional.
 *
 * Semántica idéntica a `modules`: es una lista de EXCEPCIONES, no una
 * whitelist. Sin fila para (item_key, role_id) => visible=true por defecto
 * (ver SidebarMenuService::isVisibleForRoles()). Así un item nuevo agregado
 * a $menuStructure no requiere migración de datos para ser visible a todos
 * los roles.
 *
 * `permission` (Spatie) NO se toca: sigue siendo el gate real de
 * autorización de ruta. Esta tabla solo controla si el link aparece en el
 * sidebar para un rol que igual podría (o no) tener el permiso.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sidebar_menu_visibilities')) {
            return;
        }

        Schema::create('sidebar_menu_visibilities', function (Blueprint $table) {
            $table->snowflake('id')->primary();

            // Coincide con la clave del item en $menuStructure: 'games_bingo'
            // (padre) o 'games_bingo.my_games' (submenú, prefijo del padre +
            // '.' + clave del subitem) — evita colisiones entre submenús de
            // distintos padres que compartan clave.
            $table->string('item_key', 150);

            $table->foreignSnowflake('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();

            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['item_key', 'role_id']);
            $table->index('item_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidebar_menu_visibilities');
    }
};
