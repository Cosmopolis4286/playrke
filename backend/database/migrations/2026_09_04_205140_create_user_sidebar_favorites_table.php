<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Accesos directos personalizables por usuario en el sidebar. `item_key`
 * usa el mismo formato que sidebar_menu_visibilities ('parent' o
 * 'parent.child'), resuelto contra SidebarMenuService::$menuStructure en
 * tiempo de lectura (ver SidebarMenuService::getFavorites()) — cualquier
 * item eliminado del árbol o que el usuario perdió por permiso/rol se
 * filtra ahí, sin necesidad de limpiar filas huérfanas aquí.
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('user_sidebar_favorites');
    }

    public function up() : void
    {
        Schema::create('user_sidebar_favorites', function(Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('item_key', 150);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'item_key']);
        });
    }
};
