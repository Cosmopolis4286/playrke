<?php

declare(strict_types=1);

namespace App\Services\Directory;

use App\Exceptions\Domain\Directory\DirectoryException;
use App\Services\Roles\RoleUserService;

/**
 * Resuelve el "usuario dueño efectivo" del directorio que se está
 * consultando/gestionando en una request: por defecto es el propio actor
 * autenticado, salvo que:
 *   1. venga un target_user_id explícito en la request, Y
 *   2. el actor autenticado tenga rol admin/superadmin.
 *
 * Sin esto, admin/superadmin no tenían forma de ver o gestionar el
 * directorio bancario de otro usuario — necesario para soporte y
 * verificación antifraude antes de aprobar un retiro (confirmar que la
 * cuenta del comprobante coincide con lo registrado). Cualquier otro rol
 * que envíe target_user_id recibe 403 — nunca se ignora en silencio, para
 * que un intento de acceso indebido sea visible como error, no como un
 * fallback silencioso al propio directorio.
 */
final class DirectoryAdminAccessService
{
    private const ADMIN_ROLES = ['admin', 'superadmin'];

    public function __construct(
        private readonly RoleUserService $roleUserService,
    ) {
    }

    /**
     * @return array{owner_user_id: int, actor_user_id: int, actor_role: string, is_impersonation: bool}
     *
     * @throws DirectoryException si se pidió target_user_id sin privilegio admin
     */
    public function resolve(int $actorUserId, ?string $targetUserId) : array
    {
        $actorRole = $this->roleUserService->getActiveUserRolePrefix($actorUserId);

        if (! $targetUserId || (string) $targetUserId === (string) $actorUserId) {
            return [
                'owner_user_id' => $actorUserId,
                'actor_user_id' => $actorUserId,
                'actor_role' => $actorRole,
                'is_impersonation' => false,
            ];
        }

        if (! in_array($actorRole, self::ADMIN_ROLES, true)) {
            throw DirectoryException::targetUserNotAllowed();
        }

        return [
            'owner_user_id' => (int) $targetUserId,
            'actor_user_id' => $actorUserId,
            'actor_role' => $actorRole,
            'is_impersonation' => true,
        ];
    }
}
