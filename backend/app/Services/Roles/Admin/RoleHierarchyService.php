<?php

namespace App\Services\Roles\Admin;

use App\Models\Audit;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Collection as SupportCollection;

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
        $statusChanges = $this->lastStatusChangeByRoleId();

        $roots = $roles->filter(fn (Role $role) => $this->parentOf($role) === null || !$roles->has($this->parentOf($role)));

        return $roots
            ->sortBy('sort_order')
            ->values()
            ->map(fn (Role $role) => $this->serializeNode($role, $nodesByParent, $statusChanges))
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

    /** @param array<string, ?\Illuminate\Support\Carbon> $statusChanges role_id (string) => fecha del último cambio de status */
    private function serializeNode(Role $role, Collection $nodesByParent, array $statusChanges): array
    {
        $prefixKey = strtolower((string) $role->prefix);

        $children = $nodesByParent->get($prefixKey, collect())
            ->sortBy('sort_order')
            ->values()
            ->map(fn (Role $child) => $this->serializeNode($child, $nodesByParent, $statusChanges))
            ->all();

        return [
            'id' => $role->id,
            'name' => $role->name,
            'prefix' => $role->prefix,
            'description' => $role->description,
            'status' => $role->status,
            'status_changed_at' => $statusChanges[(string) $role->id] ?? null,
            'sort_order' => $role->sort_order,
            'icon' => $role->icon,
            'color' => $role->color,
            'guard_name' => $role->guard_name,
            'users_count' => $role->users_count,
            'permissions_count' => $role->permissions_count,
            'children' => $children,
        ];
    }

    /**
     * Último cambio de `status` por rol, leído de la auditoría (Role usa el
     * trait Auditable). new_values guarda TODOS los atributos en cada
     * 'updated' (no solo el campo modificado), así que se filtra en PHP
     * comparando old_values.status vs new_values.status — el volumen de
     * roles/audits es bajo (decenas), no amerita una consulta JSON compleja.
     *
     * Compartido con RoleService::getAll() (vista en lista de
     * RoleManagerModal.vue) para que ambas pantallas muestren la misma fecha
     * sin duplicar la consulta.
     *
     * @return array<string, ?\Illuminate\Support\Carbon> role_id (string) => fecha del cambio
     */
    public function lastStatusChangeByRoleId(): array
    {
        return Audit::ofModel(Role::class)
            ->ofEvent('updated')
            ->latest('created_at')
            ->get(['auditable_id', 'old_values', 'new_values', 'created_at'])
            ->map(function (Audit $audit) {
                $audit->old_values = $this->decodeAuditValues($audit->old_values);
                $audit->new_values = $this->decodeAuditValues($audit->new_values);
                return $audit;
            })
            ->filter(fn (Audit $audit) =>
                array_key_exists('status', $audit->old_values ?? [])
                && array_key_exists('status', $audit->new_values ?? [])
                && $audit->old_values['status'] !== $audit->new_values['status']
            )
            ->groupBy(fn (Audit $audit) => (string) (is_array($audit->auditable_id) ? ($audit->auditable_id[0] ?? '') : $audit->auditable_id))
            ->map(fn (SupportCollection $audits) => $audits->first()->created_at)
            ->all();
    }

    /**
     * Decodifica old_values/new_values tolerando audits antiguos guardados
     * con doble serialización (Auditable::serializeValue() hacía json_encode()
     * manual sobre columnas que ya tienen cast 'array', ver fix en ese trait)
     * — esos registros históricos quedaron con un string JSON dentro de la
     * columna JSON en vez de un objeto, así que el cast del modelo entrega
     * ese string tal cual y hay que decodificarlo una vez más aquí.
     */
    private function decodeAuditValues(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
