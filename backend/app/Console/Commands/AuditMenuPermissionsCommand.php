<?php

namespace App\Console\Commands;

use App\Services\Menu\SidebarMenuService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

/**
 * Detecta desincronizaciones entre el permiso que exige un ítem del sidebar
 * (SidebarMenuService::$menuStructure) y el middleware real de su ruta.
 *
 * Nace de una auditoría manual que encontró varios huecos reales de este
 * tipo (ver commits de fix en games_bingo.*): el sidebar y la ruta son dos
 * arrays PHP mantenidos a mano sin ninguna validación cruzada, así que un
 * ítem puede quedar con un permiso más laxo O más estricto que su ruta sin
 * que nada lo detecte hasta que alguien lo nota en producción.
 *
 * Dos tipos de hallazgo:
 * - DESALINEADO: la ruta exige un permiso Spatie explícito
 *   (multiPermission:/permission:) y el permiso del ítem de menú no está
 *   entre los aceptados → alguien puede VER el ítem sin poder usarlo, o
 *   viceversa, según cuál sea más laxo.
 * - RUTA_ABIERTA: el menú exige un permiso pero la ruta no tiene NINGÚN
 *   middleware de autorización (ni permiso, ni role:, ni policy en el
 *   controller detectable por convención) → riesgo real de acceso por URL
 *   directa a quien no ve el ítem en el menú.
 *
 * Limitaciones conocidas (para no interpretar falsos positivos como bugs):
 * - No sigue redirects: un ítem de menú apuntando a un alias que hace
 *   redirect() a una ruta protegida se reporta con el middleware del alias,
 *   no el de la ruta final (ver 'trivia.questions.index' en trivia.php).
 * - No detecta $this->authorize(...) llamado a mano dentro de un método del
 *   controller (ej. GameController, AppParameterController) — solo ve
 *   middleware declarado en la ruta (incluye el 'can:' que
 *   authorizeResource() inyecta automáticamente). Antes de tratar un
 *   RUTA_ABIERTA como bug real, confirmar leyendo el controller.
 * - Cuando la ruta usa 'role:' (rol duro) y el menú usa un permiso Spatie
 *   distinto, no se comparan 1:1 (son dos sistemas de autorización
 *   distintos) — se reporta aparte como INFO, no como hallazgo, y requiere
 *   juicio humano para confirmar que ambos producen el mismo resultado.
 *
 * Un TERCER chequeo, independiente del cruce menú↔ruta: ítems que
 * 'superadmin' ve pero 'admin' NO, pese a que 'admin' es "casi todos los
 * permisos excepto los de SuperAdmin" (ver comentario en
 * RolePermissionSeeder::run()). El bypass de permisos en
 * SidebarMenuService::generateMenu() es EXCLUSIVO de superadmin
 * ($userPermissions = null solo si hasRole('superadmin')) — un admin normal
 * pasa por el chequeo de permiso como cualquier otro rol. Si el seeder
 * olvida asignarle un permiso nuevo a admin, admin queda ciego a ese ítem
 * SIN QUE NADA LO AVISE, porque superadmin (quien probablemente prueba los
 * cambios) nunca pasa por ese chequeo y no lo nota. Se excluyen los casos ya
 * confirmados como solo-superadmin por diseño (ver
 * self::ADMIN_EXCLUDED_PERMISSIONS) y los perfiles-propios de otro rol
 * (*.manage-own — un admin no es commerce/subsidiary/seller, no debería
 * tener uno).
 *
 * Uso: `php artisan menu:audit-permissions` — sale con código 1 si encuentra
 * algún DESALINEADO, RUTA_ABIERTA o ADMIN_SIN_ACCESO, pensado para correr en
 * CI o antes de cada release que toque permisos/rutas/sidebar.
 */
class AuditMenuPermissionsCommand extends Command
{
    /**
     * Permisos que un ítem del sidebar puede exigir sin que se reporte como
     * hallazgo aunque 'admin' no los tenga. Dos motivos distintos:
     *
     * - Solo-superadmin por diseño explícito en el propio código (ver
     *   docblocks de PermissionPolicy/ModulePolicy/
     *   SidebarMenuVisibilityPolicy/DashboardWidgetVisibilityPolicy y los
     *   comentarios "SuperAdmin — permiso protegido system.logs/system.jobs"
     *   en routes/modules/admin.php).
     * - Función de JUGADOR/CONSUMIDOR, no de gestión administrativa — un
     *   admin gestiona la plataforma, no tiene billetera/inventario propios
     *   ni juega Trivia. Ojo: *.manage-own (perfil propio de otro rol de
     *   negocio) se excluye aparte por sufijo en auditAdminBlindSpots(), no
     *   aquí — esta lista es solo para permisos sin ese patrón.
     *
     * Si agregas un ítem nuevo aquí, deja el motivo — esta lista es la única
     * defensa contra que "es correcto que admin no lo vea" se use para
     * silenciar un hallazgo real sin revisarlo primero.
     *
     * @var array<int, string>
     */
    private const ADMIN_EXCLUDED_PERMISSIONS = [
        'superadmin.access',
        'system.logs',
        'system.jobs',
        'wallet.view',              // billetera propia de jugador/customer
        'inventory.view',           // inventario propio de jugador (marketplace P2P)
        'games.trivia.play',        // jugar Trivia — rol de consumidor, no de gestión
        // sidebar-menu.view/dashboard-widgets.view: migrados de role:superadmin
        // a permisos granulares (ver migración 2026_09_27_000001) pero
        // "mismo nivel de acceso de antes" según el propio comentario de
        // routes/modules/admin.php — hoy solo superadmin los tiene, deliberado.
        'sidebar-menu.view',
        'dashboard-widgets.view',
    ];

    /**
     * Permisos de ítem de menú que pueden ser MÁS ESTRICTOS que los de su
     * ruta sin que sea un bug — la ruta acepta un conjunto más amplio de
     * roles porque sirve a otro propósito compartido, pero el ítem del menú
     * solo debe mostrarse a quien realmente necesita esa acción específica.
     * Cada entrada debe estar documentada en su definición dentro de
     * SidebarMenuService::$menuStructure (ver 'generate_card').
     *
     * @var array<int, string>
     */
    private const MENU_INTENTIONALLY_STRICTER = [
        'sellers.manage-own', // 'Generar Cartón': solo seller, aunque la ruta también admite games.bingo/game_rooms (más amplio, sirve también a compra de jugador)
    ];

    protected $description = 'Audita que el permiso de cada ítem del sidebar coincida con el middleware real de su ruta';

    protected $signature = 'menu:audit-permissions {--verbose-info : Mostrar también los casos INFO (role: vs permiso, sin comparación automática)}';

    public function handle(SidebarMenuService $menuService) : int
    {
        $items = $this->extractMenuItems($menuService->menuStructure);

        if (empty($items)) {
            $this->warn('No se encontraron ítems de menú con ruta — revisa SidebarMenuService::$menuStructure.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Auditando %d ítems de menú con ruta...', count($items)));
        $this->newLine();

        $misaligned = [];
        $openRoutes = [];
        $infoCases = [];

        foreach ($items as $itemKey => $info) {
            $result = $this->auditItem($itemKey, $info);

            match ($result['type']) {
                'DESALINEADO' => $misaligned[] = $result,
                'RUTA_ABIERTA' => $openRoutes[] = $result,
                'INFO' => $infoCases[] = $result,
                default => null,
            };
        }

        $this->reportFindings('⚠️  DESALINEADOS (permiso del menú no coincide con el de la ruta)', $misaligned);
        $this->reportFindings('🚨 RUTAS ABIERTAS (menú exige permiso, ruta no tiene ninguno detectable)', $openRoutes);

        if ($this->option('verbose-info') && ! empty($infoCases)) {
            $this->reportFindings('ℹ️  INFO (ruta usa role: — requiere confirmación manual)', $infoCases);
        }

        $adminBlindSpots = $this->auditAdminBlindSpots($items);
        $this->reportAdminBlindSpots($adminBlindSpots);

        $totalIssues = count($misaligned) + count($openRoutes) + count($adminBlindSpots);

        if ($totalIssues === 0) {
            $this->info('✔ Sin desincronizaciones detectadas entre sidebar, rutas y permisos de admin.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error(sprintf(
            '✘ %d problema(s) encontrado(s). Antes de "arreglar" un RUTA_ABIERTA, confirma leyendo el controller: puede tener $this->authorize(...) inline (ver docblock de esta clase).',
            $totalIssues,
        ));

        return self::FAILURE;
    }

    /**
     * Ítems visibles para superadmin (vía bypass) que 'admin' NO puede ver,
     * excluyendo los ya confirmados como solo-superadmin
     * (self::ADMIN_EXCLUDED_PERMISSIONS) y los perfiles-propios de otro rol
     * (*.manage-own).
     *
     * @param array<string, array{route: string, permission: string|null}> $items
     *
     * @return array<int, array{item: string, permission: string}>
     */
    private function auditAdminBlindSpots(array $items) : array
    {
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();

        if (! $adminRole) {
            return [];
        }

        $blindSpots = [];

        foreach ($items as $itemKey => $info) {
            $permission = $info['permission'];

            if (! $permission) {
                continue;
            }
            if (str_ends_with($permission, '.manage-own')) {
                continue;
            }
            if (in_array($permission, self::ADMIN_EXCLUDED_PERMISSIONS, true)) {
                continue;
            }
            if ($adminRole->hasPermissionTo($permission)) {
                continue;
            }

            $blindSpots[] = ['item' => $itemKey, 'permission' => $permission];
        }

        return $blindSpots;
    }

    /**
     * @param array{route: string, permission: string|null} $info
     *
     * @return array{type: string, item: string, route: string, menu_permission: string|null, route_permissions: array<int, string>, roles: array<int, string>}
     */
    private function auditItem(string $itemKey, array $info) : array
    {
        $routeName = $info['route'];
        $menuPerm = $info['permission'];

        $route = Route::getRoutes()->getByName($routeName);

        if (! $route) {
            return [
                'type' => 'RUTA_ABIERTA',
                'item' => $itemKey,
                'route' => $routeName . ' (NO EXISTE)',
                'menu_permission' => $menuPerm,
                'route_permissions' => [],
                'roles' => [],
            ];
        }

        [$permissions, $roles] = $this->parseMiddleware($route->gatherMiddleware());

        $type = 'OK';

        if ($menuPerm && in_array($menuPerm, self::MENU_INTENTIONALLY_STRICTER, true)) {
            // Deliberadamente más estricto que la ruta — no comparar (ver docblock de la constante).
        } elseif ($menuPerm && $permissions && ! in_array($menuPerm, $permissions, true)) {
            $type = 'DESALINEADO';
        } elseif ($menuPerm && ! $permissions && ! $roles) {
            $type = 'RUTA_ABIERTA';
        } elseif ($menuPerm && $roles && ! $permissions) {
            $type = 'INFO';
        }

        return [
            'type' => $type,
            'item' => $itemKey,
            'route' => $routeName,
            'menu_permission' => $menuPerm,
            'route_permissions' => $permissions,
            'roles' => $roles,
        ];
    }

    /**
     * @return array<string, array{route: string, permission: string|null}>
     */
    private function extractMenuItems(array $structure) : array
    {
        $items = [];

        foreach ($structure as $key => $item) {
            if (! empty($item['route'])) {
                $items[$key] = ['route' => $item['route'], 'permission' => $item['permission'] ?? null];
            }
            foreach ($item['submenu'] ?? [] as $subKey => $subItem) {
                if (! empty($subItem['route'])) {
                    $items["{$key}.{$subKey}"] = ['route' => $subItem['route'], 'permission' => $subItem['permission'] ?? null];
                }
            }
        }

        return $items;
    }

    /**
     * @param array<int, string> $middleware
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function parseMiddleware(array $middleware) : array
    {
        $permissions = [];
        $roles = [];

        foreach ($middleware as $mw) {
            if (str_starts_with($mw, 'multiPermission:')) {
                $permissions = array_merge($permissions, explode(',', substr($mw, strlen('multiPermission:'))));
            } elseif (str_starts_with($mw, 'permission:')) {
                $permissions = array_merge($permissions, explode('|', substr($mw, strlen('permission:'))));
            } elseif (str_starts_with($mw, 'role:')) {
                $roles = array_merge($roles, explode(',', substr($mw, strlen('role:'))));
            }
        }

        return [$permissions, $roles];
    }

    /**
     * @param array<int, array{item: string, permission: string}> $blindSpots
     */
    private function reportAdminBlindSpots(array $blindSpots) : void
    {
        if (empty($blindSpots)) {
            return;
        }

        $this->line('<fg=yellow>👁️  ADMIN_SIN_ACCESO (superadmin ve el ítem vía bypass, admin no tiene el permiso)</>');

        foreach ($blindSpots as $spot) {
            $this->line(sprintf('  %-40s requiere \'%s\' — admin no lo tiene', $spot['item'], $spot['permission']));
        }

        $this->newLine();
    }

    /**
     * @param array<int, array{item: string, route: string, menu_permission: string|null, route_permissions: array<int, string>, roles: array<int, string>}> $findings
     */
    private function reportFindings(string $title, array $findings) : void
    {
        if (empty($findings)) {
            return;
        }

        $this->line("<fg=yellow>{$title}</>");

        foreach ($findings as $finding) {
            $menuPermStr = $finding['menu_permission'] ?? '(ninguno)';
            $routePermStr = $finding['route_permissions'] ? implode('|', $finding['route_permissions']) : '(ninguno)';
            $rolesStr = $finding['roles'] ? implode(',', $finding['roles']) : '';

            $this->line(sprintf(
                '  %-40s route=%-30s menu=%-30s route_perms=%-35s %s',
                $finding['item'],
                $finding['route'],
                $menuPermStr,
                $routePermStr,
                $rolesStr ? "role={$rolesStr}" : '',
            ));
        }

        $this->newLine();
    }
}
