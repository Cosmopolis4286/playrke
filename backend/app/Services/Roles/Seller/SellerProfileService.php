<?php

namespace App\Services\Roles\Seller;

use App\Models\Roles\Seller;
use App\Services\Roles\AdminProfileAccessService;
use App\Services\Security\UserContextService;

/**
 * Autogestión de la ficha propia de un Seller (taquilla/vendedor) —
 * distinto de ManagerSellerService (gestión por su Commerce/Subsidiary,
 * protegido por sellers.*). Por defecto SIEMPRE resuelve el registro Seller
 * del usuario autenticado; admin/superadmin pueden pasar $targetUserId
 * (resuelto por AdminProfileAccessService) para inspeccionar la ficha de un
 * vendedor específico — sin esto, "Mi Perfil" en el sidebar era un 404
 * garantizado para admin/superadmin (no tienen registro Seller propio).
 */
final class SellerProfileService
{
    public function __construct(
        private readonly UserContextService $userContext,
        private readonly AdminProfileAccessService $adminAccess,
    ) {}

    private function ownSellerOrFail(?string $targetUserId = null): Seller
    {
        $actorId = $this->userContext->resolveUserId();
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        $seller = Seller::where('user_id', $access['owner_user_id'])->first();

        abort_if(!$seller, 404, 'No se encontró un vendedor asociado a este usuario.');

        return $seller;
    }

    public function showOwn(?string $targetUserId = null): array
    {
        $seller = $this->ownSellerOrFail($targetUserId);
        $seller->load(['country:id,name', 'user:id,username,email']);

        $commerce = $seller->resolveCommerce();

        return [
            'id'           => $seller->hashed_id,
            'name'         => $seller->name,
            'phone'        => $seller->phone,
            'status'       => $seller->status,
            'status_badge' => $seller->status_badge,
            'country'      => $seller->country?->name,
            'reports_to'   => [
                'name' => $seller->manager_name,
                'type' => $seller->subsidiary_id ? 'subsidiary' : 'commerce',
                'commerce_name' => $commerce?->name,
            ],
            'user'         => ['username' => $seller->user->username, 'email' => $seller->user->email],
        ];
    }

    /**
     * Solo estos 2 campos son autogestionables — comisión/manager/agencia
     * nunca llegan aquí (array_intersect_key es defensa en profundidad,
     * aunque el Request ya filtre los campos aceptados). admin/superadmin
     * pueden editar vía $targetUserId igual que showOwn().
     *
     * @param array{name?: string, phone?: string} $data
     */
    public function updateOwn(array $data, ?string $targetUserId = null): void
    {
        $seller = $this->ownSellerOrFail($targetUserId);
        $seller->update(array_intersect_key($data, array_flip(['name', 'phone'])));
    }
}
