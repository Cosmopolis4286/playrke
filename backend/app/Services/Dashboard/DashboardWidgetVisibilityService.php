<?php

namespace App\Services\Dashboard;

use App\Models\DashboardWidgetVisibility;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Gestión de visibilidad de widgets del dashboard (StatCards, conteos de
 * entidad y accesos rápidos de Admin/SuperAdmin) — mismo patrón que
 * SidebarMenuVisibilityService: matriz item_key × role_id, lista de
 * excepciones (ausencia de fila = visible=true).
 *
 * Dos capas de filtrado independientes, aplicadas en orden:
 *   1. Permiso Spatie real del usuario (isAuthorizedFor()) — NUNCA se salta,
 *      ni siquiera esta visibilidad configurable puede mostrar algo que el
 *      permiso prohíbe.
 *   2. Visibilidad configurada aquí (isVisibleForRole()) — un superadmin
 *      puede ocultar un widget para un rol aunque ese rol SÍ tenga el
 *      permiso (ej. simplificar el dashboard de 'admin' quitando "Tesoreros"
 *      aunque admin tenga treasurer.view).
 */
final class DashboardWidgetVisibilityService
{
    /**
     * Catálogo de widgets configurables — la fuente de verdad de qué
     * widgets EXISTEN por dashboard, y qué permiso Spatie los protege
     * (null = sin restricción de permiso, solo el rol dueño del dashboard).
     * Debe mantenerse en sincronía manual con AdminDashboardContextService /
     * SuperAdminDashboardContextService / useDashboardQuickLinks.ts — si se
     * agrega un widget ahí sin registrarlo aquí, DashboardWidgetFilter lo
     * deja pasar sin filtrar (fail-open, mismo criterio que el sidebar).
     *
     * @var array<int, array{key: string, dashboard: string, type: string, label: string, permission: ?string}>
     */
    private const CATALOG = [
        // ── Admin: StatCards ──
        ['key' => 'admin.stat.totalUsers',        'dashboard' => 'admin', 'type' => 'stat', 'label' => 'Usuarios totales',   'permission' => null],
        ['key' => 'admin.stat.newUsersThisMonth',  'dashboard' => 'admin', 'type' => 'stat', 'label' => 'Nuevos este mes',    'permission' => null],
        ['key' => 'admin.stat.cardsToday',         'dashboard' => 'admin', 'type' => 'stat', 'label' => 'Cartones hoy',       'permission' => 'games.bingo'],
        ['key' => 'admin.stat.activeRooms',        'dashboard' => 'admin', 'type' => 'stat', 'label' => 'Salas activas',      'permission' => 'games.bingo'],

        // ── Admin: conteos de entidad ──
        ['key' => 'admin.entity.Administradores', 'dashboard' => 'admin', 'type' => 'entity', 'label' => 'Administradores', 'permission' => null],
        ['key' => 'admin.entity.Comercios',       'dashboard' => 'admin', 'type' => 'entity', 'label' => 'Comercios',       'permission' => 'commerces.view'],
        ['key' => 'admin.entity.Vendedores',      'dashboard' => 'admin', 'type' => 'entity', 'label' => 'Vendedores',      'permission' => 'sellers.view'],
        ['key' => 'admin.entity.Clientes',        'dashboard' => 'admin', 'type' => 'entity', 'label' => 'Clientes',        'permission' => 'customers.view'],
        ['key' => 'admin.entity.Jugadores',       'dashboard' => 'admin', 'type' => 'entity', 'label' => 'Jugadores',       'permission' => null],
        ['key' => 'admin.entity.Tesoreros',       'dashboard' => 'admin', 'type' => 'entity', 'label' => 'Tesoreros',       'permission' => 'treasury.view'],

        // ── Admin: accesos rápidos ──
        ['key' => 'admin.quicklink.Administradores', 'dashboard' => 'admin', 'type' => 'quicklink', 'label' => 'Acceso: Administradores', 'permission' => null],
        ['key' => 'admin.quicklink.Permisos',         'dashboard' => 'admin', 'type' => 'quicklink', 'label' => 'Acceso: Permisos',         'permission' => 'superadmin.access'],
        ['key' => 'admin.quicklink.Usuarios',         'dashboard' => 'admin', 'type' => 'quicklink', 'label' => 'Acceso: Usuarios',         'permission' => 'users.view'],
        ['key' => 'admin.quicklink.Comercios',        'dashboard' => 'admin', 'type' => 'quicklink', 'label' => 'Acceso: Comercios',        'permission' => 'commerces.view'],
        ['key' => 'admin.quicklink.Vendedores',       'dashboard' => 'admin', 'type' => 'quicklink', 'label' => 'Acceso: Vendedores',       'permission' => 'sellers.view'],
        ['key' => 'admin.quicklink.Clientes',         'dashboard' => 'admin', 'type' => 'quicklink', 'label' => 'Acceso: Clientes',         'permission' => 'customers.view'],
        ['key' => 'admin.quicklink.Jugadores',        'dashboard' => 'admin', 'type' => 'quicklink', 'label' => 'Acceso: Jugadores',        'permission' => null],
        ['key' => 'admin.quicklink.Tesoreros',        'dashboard' => 'admin', 'type' => 'quicklink', 'label' => 'Acceso: Tesoreros',        'permission' => 'treasury.view'],

        // ── SuperAdmin: StatCards ──
        ['key' => 'superadmin.stat.totalUsers',       'dashboard' => 'superadmin', 'type' => 'stat', 'label' => 'Usuarios totales',      'permission' => null],
        ['key' => 'superadmin.stat.totalAdmins',      'dashboard' => 'superadmin', 'type' => 'stat', 'label' => 'Administradores',       'permission' => null],
        ['key' => 'superadmin.stat.activeRooms',      'dashboard' => 'superadmin', 'type' => 'stat', 'label' => 'Salas de Bingo activas', 'permission' => 'games.bingo'],
        ['key' => 'superadmin.stat.totalPermissions', 'dashboard' => 'superadmin', 'type' => 'stat', 'label' => 'Permisos totales',      'permission' => 'superadmin.access'],
        ['key' => 'superadmin.stat.pendingKyc',       'dashboard' => 'superadmin', 'type' => 'stat', 'label' => 'KYC pendientes',        'permission' => 'kyc.view'],

        // ── SuperAdmin: conteos de entidad (mismo set que admin) ──
        ['key' => 'superadmin.entity.Administradores', 'dashboard' => 'superadmin', 'type' => 'entity', 'label' => 'Administradores', 'permission' => null],
        ['key' => 'superadmin.entity.Comercios',       'dashboard' => 'superadmin', 'type' => 'entity', 'label' => 'Comercios',       'permission' => null],
        ['key' => 'superadmin.entity.Vendedores',      'dashboard' => 'superadmin', 'type' => 'entity', 'label' => 'Vendedores',      'permission' => null],
        ['key' => 'superadmin.entity.Clientes',        'dashboard' => 'superadmin', 'type' => 'entity', 'label' => 'Clientes',        'permission' => null],
        ['key' => 'superadmin.entity.Jugadores',       'dashboard' => 'superadmin', 'type' => 'entity', 'label' => 'Jugadores',       'permission' => null],
        ['key' => 'superadmin.entity.Tesoreros',       'dashboard' => 'superadmin', 'type' => 'entity', 'label' => 'Tesoreros',       'permission' => null],

        // ── SuperAdmin: accesos rápidos ──
        ['key' => 'superadmin.quicklink.Administradores', 'dashboard' => 'superadmin', 'type' => 'quicklink', 'label' => 'Acceso: Administradores', 'permission' => null],
        ['key' => 'superadmin.quicklink.Permisos',         'dashboard' => 'superadmin', 'type' => 'quicklink', 'label' => 'Acceso: Permisos',         'permission' => null],
        ['key' => 'superadmin.quicklink.Usuarios',         'dashboard' => 'superadmin', 'type' => 'quicklink', 'label' => 'Acceso: Usuarios',         'permission' => null],
        ['key' => 'superadmin.quicklink.Comercios',        'dashboard' => 'superadmin', 'type' => 'quicklink', 'label' => 'Acceso: Comercios',        'permission' => null],
        ['key' => 'superadmin.quicklink.Vendedores',       'dashboard' => 'superadmin', 'type' => 'quicklink', 'label' => 'Acceso: Vendedores',       'permission' => null],
        ['key' => 'superadmin.quicklink.Clientes',         'dashboard' => 'superadmin', 'type' => 'quicklink', 'label' => 'Acceso: Clientes',         'permission' => null],
        ['key' => 'superadmin.quicklink.Jugadores',        'dashboard' => 'superadmin', 'type' => 'quicklink', 'label' => 'Acceso: Jugadores',        'permission' => null],
        ['key' => 'superadmin.quicklink.Tesoreros',        'dashboard' => 'superadmin', 'type' => 'quicklink', 'label' => 'Acceso: Tesoreros',        'permission' => null],
    ];

    /**
     * @param array<int, array{item_key: string, role_id: string, is_visible: bool}> $changes
     */
    public function applyChanges(array $changes) : void
    {
        DB::transaction(function() use ($changes) {
            foreach ($changes as $change) {
                DashboardWidgetVisibility::updateOrCreate(
                    ['item_key' => $change['item_key'], 'role_id' => $change['role_id']],
                    ['is_visible' => $change['is_visible']],
                );
            }
        });
    }

    /**
     * Filtro listo para usar en un request: precarga la matriz de
     * visibilidad UNA sola vez (evita una query por widget al filtrar un
     * dashboard completo de ~15-20 entradas) y expone isVisibleFor($user,
     * $itemKey, $permission) con la misma semántica documentada ahí.
     */
    public function filterFor(User $user) : DashboardWidgetFilter
    {
        return new DashboardWidgetFilter($user, $this->getVisibilityMatrix());
    }

    /**
     * @return array<int, array{key: string, dashboard: string, type: string, label: string, permission: ?string}>
     */
    public function getCatalog() : array
    {
        return self::CATALOG;
    }

    /**
     * Roles activos, ordenados, para las columnas de la matriz — mismo
     * criterio que SidebarMenuVisibilityService::getRoles().
     *
     * @return array<int, array{id: string, name: string, prefix: string}>
     */
    public function getRoles() : array
    {
        return Role::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'prefix'])
            ->map(fn (Role $role) => [
                'id' => (string) $role->id,
                'name' => $role->name,
                'prefix' => $role->prefix ?? $role->name,
            ])
            ->toArray();
    }

    /**
     * item_key => [role_id => bool]. Solo incluye excepciones explícitas.
     *
     * @return array<string, array<string, bool>>
     */
    public function getVisibilityMatrix() : array
    {
        return DashboardWidgetVisibility::query()
            ->get(['item_key', 'role_id', 'is_visible'])
            ->groupBy('item_key')
            ->map(fn ($rows) => $rows->pluck('is_visible', 'role_id')->toArray())
            ->toArray();
    }
}
