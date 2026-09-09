<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orden de items por rol, misma semántica de excepción que is_visible:
 * null = usar el orden natural de $menuStructure (ver
 * SidebarMenuService::generateMenu()).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guard de idempotencia — sin esto, un migrate:fresh que corre en
        // paralelo con otra sesión que ya deja la columna físicamente
        // creada pero sin registrar en `migrations` revienta con "Column
        // already exists" y aborta toda la corrida de tests antes de
        // llegar al seeder (mismo problema ya visto con receipt_hash).
        if (Schema::hasColumn('sidebar_menu_visibilities', 'sort_order')) {
            return;
        }

        Schema::table('sidebar_menu_visibilities', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->nullable()->after('is_visible');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sidebar_menu_visibilities', 'sort_order')) {
            return;
        }

        Schema::table('sidebar_menu_visibilities', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
