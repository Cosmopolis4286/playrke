<?php

namespace App\Services\Roles\Subsidiary;

use App\Models\Roles\Subsidiary;
use App\Services\Security\UserContextService;

/**
 * Autogestión de la ficha propia de una Subsidiary (agencia/sucursal) —
 * distinto de AdminSubsidiaryService (gestión de terceros por admin/commerce,
 * protegido por subsidiaries.*). Este servicio SIEMPRE resuelve el registro
 * Subsidiary del usuario autenticado, nunca recibe un id por parámetro — por
 * diseño, es imposible que opere sobre el registro de otra agencia.
 */
final class SubsidiaryProfileService
{
    public function __construct(
        private readonly UserContextService $userContext,
    ) {}

    private function ownSubsidiaryOrFail(): Subsidiary
    {
        $userId = $this->userContext->resolveUserId();
        $subsidiary = Subsidiary::where('user_id', $userId)->first();

        abort_if(!$subsidiary, 404, 'No se encontró una agencia asociada a este usuario.');

        return $subsidiary;
    }

    public function showOwn(): array
    {
        $subsidiary = $this->ownSubsidiaryOrFail();
        $subsidiary->load(['commerce:id,name,status', 'country:id,name', 'currency:id,name,symbol', 'user:id,username,email']);

        return [
            'id'           => $subsidiary->hashed_id,
            'name'         => $subsidiary->name,
            'phone'        => $subsidiary->phone,
            'brand'        => $subsidiary->brand,
            'product'      => $subsidiary->product,
            'status'       => $subsidiary->status,
            'status_badge' => $subsidiary->status_badge,
            'commerce'     => $subsidiary->commerce ? ['name' => $subsidiary->commerce->name, 'status' => $subsidiary->commerce->status] : null,
            'country'      => $subsidiary->country?->name,
            'currency'     => $subsidiary->currency ? "{$subsidiary->currency->name} ({$subsidiary->currency->symbol})" : null,
            'user'         => ['username' => $subsidiary->user->username, 'email' => $subsidiary->user->email],
        ];
    }

    /**
     * Solo estos 4 campos son autogestionables — comisión/comercio padre/
     * moneda/país nunca llegan aquí (array_intersect_key es defensa en
     * profundidad, aunque el Request ya filtre los campos aceptados).
     *
     * @param array{name?: string, phone?: string, brand?: ?string, product?: ?string} $data
     */
    public function updateOwn(array $data): void
    {
        $subsidiary = $this->ownSubsidiaryOrFail();
        $subsidiary->update(array_intersect_key($data, array_flip(['name', 'phone', 'brand', 'product'])));
    }
}
