<?php

namespace App\Services\Roles\Admin;

use App\Models\Role;
use Illuminate\Support\Collection;

/**
 * Arma el organigrama de roles combinando:
 * - Estructura de dominio FIJA (quién pertenece a quién) — refleja relaciones
 *   reales ya cableadas en el código (Commerce tiene Sellers/Subsidiaries,
 *   Seller capta ExternalPlayers, etc.), no editable desde la UI.
 * - Metadatos DINÁMICOS por rol (sort_order, description, icon, color,
 *   cantidad de usuarios y permisos) — sí editables desde /admin/permissions,
 *   ver RoleController::update().
 *
 * Roles nuevos que no aparezcan en DOMAIN_TREE se cuelgan automáticamente de
 * la raíz (superadmin) como huérfanos, para que un rol agregado solo en
 * RoleSeeder (sin tocar este mapa) siga siendo visible en el árbol.
 *
 * Las claves de DOMAIN_TREE/FUNCTIONS son Role::prefix en minúscula. Role::
 * prefix se persiste en MAYÚSCULA (ver Role::setPrefixAttribute(), que aplica
 * strtoupper()), así que todo lookup contra estos mapas pasa \$role->prefix
 * por strtolower() primero — mismo criterio que User.php y
 * RoleUserRepository.php al comparar prefijos. Role::name ('SuperAdmin',
 * 'Comercio'...) es solo el label de UI, no sirve como clave aquí.
 */
final class RoleHierarchyService
{
    /**
     * Mapa prefix (minúscula) => prefix del padre. null = raíz.
     * 'admin' y 'treasurer' son transversales (reportan directo a superadmin,
     * no dependen de un Commerce específico). 'provider' es un rol reservado
     * sin dominio propio aún (ver RoleSeeder). 'external_player' cuelga de
     * 'seller' porque es quien lo capta vía link (ver ExternalPlayer).
     */
    private const DOMAIN_TREE = [
        'superadmin' => null,
        'admin' => 'superadmin',
        'treasurer' => 'superadmin',
        'provider' => 'superadmin',
        'commerce' => 'admin',
        'subsidiary' => 'commerce',
        'seller' => 'commerce',
        'customer' => 'admin',
        'player' => 'admin',
        'external_player' => 'seller',
    ];

    private const FUNCTIONS = [
        'superadmin' => 'Control total del sistema: roles, permisos, configuración global y respaldo.',
        'admin' => 'Gestión operativa transversal: usuarios, comercios, juegos, reportes y auditoría.',
        'commerce' => 'Administra su propio negocio: sucursales, vendedores y ventas de juegos.',
        'subsidiary' => 'Sucursal dependiente de un comercio, mismos permisos operativos que Commerce.',
        'seller' => 'Vende cartones/participaciones y capta jugadores externos vía link.',
        'treasurer' => 'Responsable financiero: tesorería, tasas de cambio y reportes financieros.',
        'customer' => 'Cliente que usa los servicios de la plataforma con cuenta propia.',
        'player' => 'Jugador registrado: participa en salas de Bingo/Trivia con su propia billetera.',
        'provider' => 'Rol reservado para integración futura de proveedor externo de juegos vía API.',
        'external_player' => 'Jugador SIN cuenta, captado por un vendedor vía link único, sin login.',
    ];

    public function buildTree(): array
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->keyBy(fn (Role $role) => strtolower((string) $role->prefix));

        $nodesByParent = $this->groupByParent($roles);

        $roots = $roles->filter(fn (Role $role) => $this->parentOf($role) === null || !$roles->has($this->parentOf($role)));

        return $roots
            ->sortBy('sort_order')
            ->values()
            ->map(fn (Role $role) => $this->serializeNode($role, $nodesByParent))
            ->all();
    }

    private function parentOf(Role $role): ?string
    {
        return self::DOMAIN_TREE[strtolower((string) $role->prefix)] ?? null;
    }

    /**
     * @param Collection<string, Role> $roles
     * @return Collection<string, Collection<int, Role>>
     */
    private function groupByParent(Collection $roles): Collection
    {
        return $roles->groupBy(fn (Role $role) => $this->parentOf($role) ?? '__root__');
    }

    private function serializeNode(Role $role, Collection $nodesByParent): array
    {
        $prefixKey = strtolower((string) $role->prefix);

        $children = $nodesByParent->get($prefixKey, collect())
            ->sortBy('sort_order')
            ->values()
            ->map(fn (Role $child) => $this->serializeNode($child, $nodesByParent))
            ->all();

        return [
            'id' => $role->id,
            'name' => $role->name,
            'prefix' => $role->prefix,
            'description' => $role->description,
            'function' => self::FUNCTIONS[$prefixKey] ?? $role->description,
            'status' => $role->status,
            'sort_order' => $role->sort_order,
            'icon' => $role->icon,
            'color' => $role->color,
            'guard_name' => $role->guard_name,
            'users_count' => $role->users_count,
            'permissions_count' => $role->permissions_count,
            'children' => $children,
        ];
    }
}
