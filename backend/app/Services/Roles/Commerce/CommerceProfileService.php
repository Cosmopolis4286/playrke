<?php

namespace App\Services\Roles\Commerce;

use App\Models\Roles\Commerce;
use App\Services\Roles\AdminProfileAccessService;
use App\Services\Security\UserContextService;

/**
 * Autogestión de la ficha propia de un Commerce (comercializadora) —
 * distinto de AdminCommerceService (gestión de terceros por admin,
 * protegido por commerces.*). Por defecto SIEMPRE resuelve el registro
 * Commerce del usuario autenticado; admin/superadmin pueden pasar
 * $targetUserId (resuelto por AdminProfileAccessService) para inspeccionar
 * la ficha de un comercio específico — sin esto, "Mi Comercio" en el
 * sidebar era un 404 garantizado para admin/superadmin (no tienen registro
 * Commerce propio).
 */
final class CommerceProfileService
{
    public function __construct(
        private readonly UserContextService $userContext,
        private readonly AdminProfileAccessService $adminAccess,
    ) {}

    private function ownCommerceOrFail(?string $targetUserId = null): Commerce
    {
        $actorId = $this->userContext->resolveUserId();
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        $commerce = Commerce::where('user_id', $access['owner_user_id'])->first();

        abort_if(!$commerce, 404, 'No se encontró un comercio asociado a este usuario.');

        return $commerce;
    }

    public function showOwn(?string $targetUserId = null): array
    {
        $commerce = $this->ownCommerceOrFail($targetUserId);
        $commerce->load(['country:id,name', 'user:id,username,email']);

        return [
            'id'           => $commerce->hashed_id,
            'name'         => $commerce->name,
            'phone'        => $commerce->phone,
            'status'       => $commerce->status,
            'status_badge' => $commerce->status_badge,
            'country'      => $commerce->country?->name,
            'user'         => ['username' => $commerce->user->username, 'email' => $commerce->user->email],
        ];
    }

    /**
     * Solo estos 2 campos son autogestionables — comisión/monedas/juegos
     * nunca llegan aquí (array_intersect_key es defensa en profundidad,
     * aunque el Request ya filtre los campos aceptados). admin/superadmin
     * pueden editar vía $targetUserId igual que showOwn().
     *
     * @param array{name?: string, phone?: string} $data
     */
    public function updateOwn(array $data, ?string $targetUserId = null): void
    {
        $commerce = $this->ownCommerceOrFail($targetUserId);
        $commerce->update(array_intersect_key($data, array_flip(['name', 'phone'])));
    }
}
