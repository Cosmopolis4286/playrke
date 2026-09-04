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
        Schema::table('sidebar_menu_visibilities', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->nullable()->after('is_visible');
        });
    }

    public function down(): void
    {
        Schema::table('sidebar_menu_visibilities', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
