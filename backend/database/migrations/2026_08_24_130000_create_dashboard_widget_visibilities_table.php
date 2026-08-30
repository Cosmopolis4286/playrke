<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visibilidad por rol de widgets del dashboard (StatCards, conteos de
 * entidades y accesos rápidos de Admin/SuperAdmin) — mismo patrón que
 * sidebar_menu_visibilities: lista de EXCEPCIONES, no whitelist. Sin fila
 * para (item_key, role_id) => visible=true por defecto (ver
 * DashboardWidgetVisibilityService::isVisibleForRole()), así un widget
 * nuevo agregado en código no requiere migración de datos para ser visible.
 *
 * A diferencia del sidebar, aquí `item_key` distingue tipo de widget (stat,
 * entity, quicklink) además del dashboard (admin/superadmin) y la clave del
 * widget — ej. 'admin.stat.totalUsers', 'superadmin.entity.Tesoreros',
 * 'admin.quicklink.Permisos'. La visibilidad configurada aquí NUNCA puede
 * mostrar algo que el permiso Spatie real del usuario ya prohíbe — ese
 * chequeo sigue siendo obligatorio y se evalúa primero en el backend.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widget_visibilities', function (Blueprint $table) {
            $table->snowflake('id')->primary();

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
        Schema::dropIfExists('dashboard_widget_visibilities');
    }
};
