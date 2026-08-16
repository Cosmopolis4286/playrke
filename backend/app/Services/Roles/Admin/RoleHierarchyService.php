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
 *   tanto en el organigrama (RoleHierarchyTree.vue) como en "Gestionar roles"
 *   (RoleManagerModal.vue): ambos leen/escriben Role::description, la MISMA
 *   columna — antes el organigrama mostraba un texto hardcoded aparte
 *   (FUNCTIONS) que no coincidía con lo que "Gestionar roles" editaba,
 *   dando la falsa impresión de que eran descripciones distintas.
 *
 * Roles nuevos que no aparezcan en DOMAIN_TREE se cuelgan automáticamente de
 * la raíz (superadmin) como huérfanos, para que un rol agregado solo en
 * RoleSeeder (sin tocar este mapa) siga siendo visible en el árbol.
 *
 * Las claves de DOMAIN_TREE son Role::prefix en minúscula. Role::prefix se
 * persiste en MAYÚSCULA (ver Role::setPrefixAttribute(), que aplica
 * strtoupper()), así que todo lookup contra este mapa pasa \$role->prefix
 * por strtolower() primero — mismo criterio que User.php y
 * RoleUserRepository.php al comparar prefijos. Role::name ('SuperAdmin',
 * 'Comercio'...) es solo el label de UI, no sirve como clave aquí.
 */
final class RoleHierarchyService
{
    /**
     * Mapa prefix (minúscula) => prefix del padre. null = raíz.
     *
     * Jerarquía real de negocio: Comercializadora (commerce) → Agencia
     * (subsidiary) → Taquilla (seller) → Jugador (external_player). 'seller'
     * cuelga de 'subsidiary', no de 'commerce' directo — un seller puede
     * seguir reportando directo a un Commerce sin agencia intermedia
     * (Seller.subsidiary_id nullable, ver Seller::resolveCommerce()), pero
     * el árbol de ROLES modela la jerarquía completa de 4 niveles.
     *
     * 'admin' y 'treasurer' son transversales (reportan directo a superadmin,
     * no dependen de una Comercializadora específica). 'provider' es un rol
     * reservado sin dominio propio aún (ver RoleSeeder).
     */
    private const DOMAIN_TREE = [
        'superadmin' => null,
        'admin' => 'superadmin',
        'treasurer' => 'superadmin',
        'provider' => 'superadmin',
        'commerce' => 'admin',
        'subsidiary' => 'commerce',
        'seller' => 'subsidiary',
        'customer' => 'admin',
        'player' => 'admin',
        'external_player' => 'seller',
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
