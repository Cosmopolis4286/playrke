<?php

namespace App\Services\Roles\Subsidiary;

use App\Models\Roles\Subsidiary;
use App\Services\Roles\AdminProfileAccessService;
use App\Services\Security\UserContextService;

/**
 * Autogestión de la ficha propia de una Subsidiary (agencia/sucursal) —
 * distinto de AdminSubsidiaryService (gestión de terceros por admin/commerce,
 * protegido por subsidiaries.*). Por defecto SIEMPRE resuelve el registro
 * Subsidiary del usuario autenticado; admin/superadmin pueden pasar
 * $targetUserId (resuelto por AdminProfileAccessService) para inspeccionar
 * la ficha de una agencia específica — sin esto, "Mi Agencia" en el
 * sidebar era un 404 garantizado para admin/superadmin (no tienen registro
 * Subsidiary propio).
 */
final class SubsidiaryProfileService
{
    public function __construct(
        private readonly UserContextService $userContext,
        private readonly AdminProfileAccessService $adminAccess,
    ) {}

    private function ownSubsidiaryOrFail(?string $targetUserId = null): Subsidiary
    {
        $actorId = $this->userContext->resolveUserId();
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        $subsidiary = Subsidiary::where('user_id', $access['owner_user_id'])->first();

        abort_if(!$subsidiary, 404, 'No se encontró una agencia asociada a este usuario.');

        return $subsidiary;
    }

    public function showOwn(?string $targetUserId = null): array
    {
        $subsidiary = $this->ownSubsidiaryOrFail($targetUserId);
        $subsidiary->load(['commerce:id,name,status', 'country:id,name', 'currency:id,name,symbol', 'user:id,username,email']);

        return [
            'id'           => $subsidiary->hashed_id,
            'name'         => $subsidiary->name,
            'phone'        => $subsidiary->phone,
            'status'       => $subsidiary->status,
            'status_badge' => $subsidiary->status_badge,
            'commerce'     => $subsidiary->commerce ? ['name' => $subsidiary->commerce->name, 'status' => $subsidiary->commerce->status] : null,
            'country'      => $subsidiary->country?->name,
            'currency'     => $subsidiary->currency ? "{$subsidiary->currency->name} ({$subsidiary->currency->symbol})" : null,
            'user'         => ['username' => $subsidiary->user->username, 'email' => $subsidiary->user->email],
        ];
    }

    /**
     * Solo estos 2 campos son autogestionables — comisión/comercio padre/
     * moneda/país nunca llegan aquí (array_intersect_key es defensa en
     * profundidad, aunque el Request ya filtre los campos aceptados).
     * admin/superadmin pueden editar vía $targetUserId igual que showOwn().
     *
     * @param array{name?: string, phone?: string} $data
     */
    public function updateOwn(array $data, ?string $targetUserId = null): void
    {
        $subsidiary = $this->ownSubsidiaryOrFail($targetUserId);
        $subsidiary->update(array_intersect_key($data, array_flip(['name', 'phone'])));
    }
}
