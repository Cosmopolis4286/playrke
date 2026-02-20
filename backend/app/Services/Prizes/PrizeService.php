<?php

namespace App\Services\Prizes;

use App\DTOs\Prizes\PrizeDTO;
use App\Http\Resources\Prizes\PrizeResource;
use App\Models\Prize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de aplicación para los casos de uso del dominio Prizes.
 *
 * Responsabilidades:
 * - Orquestar casos de uso
 * - Delegar operaciones a servicios de dominio
 * - Mantener el controller desacoplado
 *
 * NO contiene lógica de dominio ni infraestructura.
 */
final class PrizeService
{
    public function __construct(
        private readonly PrizeContextService $context,
        private readonly PrizeCrudService    $crud,
    ) {}

    /**
     * Contexto completo para la página Index.
     */
    public function context(): array
    {
        return $this->context->prizeContext();
    }

    /**
     * Lista plana de premios (para selects / fetch-list).
     */
    public function fetchList(): array
    {
        return $this->context->fetchList();
    }

    /**
     * Buscar premios individuales (no-kit) por nombre o código.
     */
    public function search(string $query, ?string $excludeId = null): array
    {
        return Prize::active()
            ->individual()
            ->where('user_id', Auth::id())
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%");
            })
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'code', 'name', 'price', 'reward_type', 'kit', 'status'])
            ->map(fn ($p) => [
                'id'    => $p->id,
                'code'  => $p->code,
                'name'  => $p->name,
                'price' => $p->price,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Crear un premio.
     */
    public function create(PrizeDTO $dto): Prize
    {
        return $this->crud->create($dto);
    }

    /**
     * Detalle de un premio.
     */
    public function show(Prize $prize): PrizeResource
    {
        return $this->crud->show($prize);
    }

    /**
     * Actualizar un premio.
     *
     * @return int Número de kits cuyo precio fue recalculado.
     */
    public function update(Prize $prize, PrizeDTO $dto): int
    {
        return $this->crud->update($prize, $dto);
    }

    /**
     * Eliminación lógica (marca como INACTIVE).
     */
    public function delete(Prize $prize): void
    {
        $this->crud->delete($prize);
    }

    /**
     * Eliminación definitiva.
     */
    public function destroy(Prize $prize): void
    {
        $this->crud->destroy($prize);
    }

    /**
     * Alternar estado ACTIVE / INACTIVE.
     */
    public function toggleStatus(Prize $prize): void
    {
        $this->crud->toggleStatus($prize);
    }
}
