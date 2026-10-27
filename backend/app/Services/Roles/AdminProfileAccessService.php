<?php

declare(strict_types=1);

namespace App\Services\Roles;

use App\Exceptions\Domain\Roles\RoleResolutionException;

/**
 * Resuelve el "usuario dueño efectivo" de una ficha de autogestión propia
 * (Seller/Commerce/Subsidiary "Mi Perfil") que se está consultando: por
 * defecto es el propio actor autenticado, salvo que:
 *   1. venga un target_user_id explícito en la request, Y
 *   2. el actor autenticado tenga rol admin/superadmin.
 *
 * Sin esto, admin/superadmin no tenían forma de inspeccionar la ficha de un
 * seller/commerce/subsidiary específico — el ítem "Mi Cuenta" del sidebar
 * asume que el actor ES esa entidad (tiene un registro Seller/Commerce/
 * Subsidiary propio ligado a su user_id), lo cual nunca es cierto para
 * admin/superadmin, así que SellerProfileService::ownSellerOrFail() (y sus
 * pares de Commerce/Subsidiary) siempre devolvían 404 para ellos.
 *
 * Mismo patrón que DirectoryAdminAccessService (dominio Directory) —
 * genérico aquí porque las 3 entidades de perfil comparten exactamente la
 * misma regla de acceso, sin necesidad de una copia por entidad.
 *
 * Cualquier rol no-admin que envíe target_user_id recibe 403 — nunca se
 * ignora en silencio, para que un intento de acceso indebido sea visible
 * como error, no como un fallback silencioso a la propia ficha.
 */
final class AdminProfileAccessService
{
    private const ADMIN_ROLES = ['admin', 'superadmin'];

    public function __construct(
        private readonly RoleUserService $roleUserService,
    ) {
    }

    /**
     * @return array{owner_user_id: int, actor_user_id: int, actor_role: string, is_admin_view: bool}
     *
     * @throws RoleResolutionException si se pidió target_user_id sin privilegio admin
     */
    public function resolve(int $actorUserId, ?string $targetUserId) : array
    {
        $actorRole = $this->roleUserService->getActiveUserRolePrefix($actorUserId);

        if (! $targetUserId || (string) $targetUserId === (string) $actorUserId) {
            return [
                'owner_user_id' => $actorUserId,
                'actor_user_id' => $actorUserId,
                'actor_role' => $actorRole,
                'is_admin_view' => false,
            ];
        }

        if (! in_array($actorRole, self::ADMIN_ROLES, true)) {
            throw RoleResolutionException::targetUserNotAllowed();
        }

        return [
            'owner_user_id' => (int) $targetUserId,
            'actor_user_id' => $actorUserId,
            'actor_role' => $actorRole,
            'is_admin_view' => true,
        ];
    }
}
