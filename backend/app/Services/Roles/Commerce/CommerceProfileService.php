<?php

namespace App\Services\Roles\Commerce;

use App\Models\Roles\Commerce;
use App\Services\Security\UserContextService;

/**
 * Autogestión de la ficha propia de un Commerce (comercializadora) —
 * distinto de AdminCommerceService (gestión de terceros por admin,
 * protegido por commerces.*). Este servicio SIEMPRE resuelve el registro
 * Commerce del usuario autenticado, nunca recibe un id por parámetro — por
 * diseño, es imposible que opere sobre el registro de otro comercio.
 */
final class CommerceProfileService
{
    public function __construct(
        private readonly UserContextService $userContext,
    ) {}

    private function ownCommerceOrFail(): Commerce
    {
        $userId = $this->userContext->resolveUserId();
        $commerce = Commerce::where('user_id', $userId)->first();

        abort_if(!$commerce, 404, 'No se encontró un comercio asociado a este usuario.');

        return $commerce;
    }

    public function showOwn(): array
    {
        $commerce = $this->ownCommerceOrFail();
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
     * aunque el Request ya filtre los campos aceptados).
     *
     * @param array{name?: string, phone?: string} $data
     */
    public function updateOwn(array $data): void
    {
        $commerce = $this->ownCommerceOrFail();
        $commerce->update(array_intersect_key($data, array_flip(['name', 'phone'])));
    }
}
