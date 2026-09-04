<?php

namespace App\Services\Roles\Seller;

use App\Models\Roles\Seller;
use App\Services\Security\UserContextService;

/**
 * Autogestión de la ficha propia de un Seller (taquilla/vendedor) —
 * distinto de ManagerSellerService (gestión por su Commerce/Subsidiary,
 * protegido por sellers.*). Este servicio SIEMPRE resuelve el registro
 * Seller del usuario autenticado, nunca recibe un id por parámetro — por
 * diseño, es imposible que opere sobre el registro de otro vendedor.
 */
final class SellerProfileService
{
    public function __construct(
        private readonly UserContextService $userContext,
    ) {}

    private function ownSellerOrFail(): Seller
    {
        $userId = $this->userContext->resolveUserId();
        $seller = Seller::where('user_id', $userId)->first();

        abort_if(!$seller, 404, 'No se encontró un vendedor asociado a este usuario.');

        return $seller;
    }

    public function showOwn(): array
    {
        $seller = $this->ownSellerOrFail();
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
     * aunque el Request ya filtre los campos aceptados).
     *
     * @param array{name?: string, phone?: string} $data
     */
    public function updateOwn(array $data): void
    {
        $seller = $this->ownSellerOrFail();
        $seller->update(array_intersect_key($data, array_flip(['name', 'phone'])));
    }
}
