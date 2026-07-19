<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Añade description e is_system a permissions.
 *
 * description: PermissionController@store/update ya validan y envían este
 * campo, pero la tabla permissions (Spatie estándar) nunca la tuvo — sin esta
 * columna, crear/editar un permiso con descripción falla con QueryException
 * en cuanto se ejecuta contra una BD real (no capturado hasta ahora porque
 * el módulo no tiene tests).
 *
 * is_system: reemplaza la detección por regex que hacía el frontend
 * (PermissionTable.vue::isSystem()) por un campo real que el backend puede
 * usar para bloquear update/delete (ver PermissionPolicy). Se marcan como
 * is_system los permisos que gobiernan el propio sistema de roles/permisos y
 * la configuración global — si se editan o eliminan por error, pueden dejar
 * sin acceso administrativo a toda la plataforma.
 */
return new class extends Migration
{
    private const SYSTEM_PERMISSIONS = [
        'superadmin.access',
        'roles.view',
        'roles.create',
        'roles.edit',
        'roles.delete',
        'roles.assign',
        'system.settings',
        'system.maintenance',
        'system.logs',
        'system.backup',
    ];

    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('description', 500)->nullable()->after('guard_name');
            $table->boolean('is_system')->default(false)->after('description');
        });

        DB::table('permissions')
            ->whereIn('name', self::SYSTEM_PERMISSIONS)
            ->update(['is_system' => true]);
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['description', 'is_system']);
        });
    }
};
