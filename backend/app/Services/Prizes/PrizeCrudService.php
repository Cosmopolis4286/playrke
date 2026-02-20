<?php

namespace App\Services\Prizes;

use App\Contracts\PrizeRepositoryInterface;
use App\DTOs\Prizes\PrizeDTO;
use App\Http\Resources\Prizes\PrizeResource;
use App\Models\Prize;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de dominio responsable de operaciones CRUD sobre Premios.
 *
 * Responsabilidades:
 * - Orquestar creación y mantenimiento de Prize
 * - Delegar persistencia al repositorio
 * - Aplicar reglas de dominio simples
 *
 * NO retorna booleanos.
 * NO maneja HTTP.
 */
final class PrizeCrudService
{
    public function __construct(
        private readonly PrizeRepositoryInterface $prizeRepository,
    ) {}

    /**
     * Crear un premio.
     */
    public function create(PrizeDTO $dto): Prize
    {
        return $this->prizeRepository->create([
            'code'        => $dto->code,
            'reward_type' => $dto->rewardType,
            'name'        => $dto->name,
            'kit'         => $dto->kit ?? 'N',
            'num_items'   => $dto->numItems ?? 1,
            'kit_items'   => $dto->kitItems,
            'price'       => $dto->price,
            'description' => $dto->description,
            'user_id'     => Auth::id(),
            'status'      => 'ACTIVE',
        ]);
    }

    /**
     * Mostrar detalle de un premio.
     */
    public function show(Prize $prize): PrizeResource
    {
        return new PrizeResource($prize);
    }

    /**
     * Actualizar un premio.
     */
    public function update(Prize $prize, PrizeDTO $dto): void
    {
        $this->prizeRepository->update($prize->id, $dto->toArray());
    }

    /**
     * Eliminación lógica (soft delete → INACTIVE).
     */
    public function delete(Prize $prize): void
    {
        $this->prizeRepository->updatePrizeStatus($prize->id, 'INACTIVE');
    }

    /**
     * Eliminación definitiva.
     */
    public function destroy(Prize $prize): void
    {
        $prize->delete();
    }

    /**
     * Alternar estado ACTIVE / INACTIVE.
     */
    public function toggleStatus(Prize $prize): void
    {
        $newStatus = $prize->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $this->prizeRepository->updatePrizeStatus($prize->id, $newStatus);
    }
}
