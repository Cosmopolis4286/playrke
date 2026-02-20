<?php

namespace App\Services\Prizes;

use App\Contracts\PrizeRepositoryInterface;
use App\DTOs\Prizes\PrizeDTO;
use App\Http\Resources\Prizes\PrizeResource;
use App\Models\Prize;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
        $userId = (string) Auth::id();

        return $this->prizeRepository->create([
            'code'        => $dto->code ?: $this->generateUniqueCode($userId),
            'slug'        => $this->generateUniqueSlug($dto->name ?? '', $userId),
            'reward_type' => $dto->rewardType,
            'name'        => $dto->name,
            'kit'         => $dto->kit ?? 'N',
            'num_items'   => $dto->numItems ?? 1,
            'kit_items'   => $dto->kitItems,
            'price'       => $dto->price,
            'description' => $dto->description,
            'user_id'     => $userId,
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
     *
     * Si el precio cambia y el premio es individual, recalcula el precio
     * de todos los kits que lo contengan.
     *
     * @return int Número de kits afectados por el recálculo de precio.
     */
    public function update(Prize $prize, PrizeDTO $dto): int
    {
        $oldPrice    = (float) $prize->price;
        $newPrice    = $dto->price !== null ? (float) $dto->price : $oldPrice;
        $priceChanged = $prize->kit === 'N' && abs($newPrice - $oldPrice) > 0.0001;

        $this->prizeRepository->update($prize->id, $dto->toArray());

        if (!$priceChanged) {
            return 0;
        }

        return $this->cascadeKitPrices((string) $prize->id, (string) Auth::id());
    }

    /**
     * Recalcula el precio de todos los kits que contengan el premio dado.
     *
     * @param  string $prizeId  ID del premio individual modificado.
     * @param  string $userId   ID del usuario propietario.
     * @return int              Número de kits actualizados.
     */
    private function cascadeKitPrices(string $prizeId, string $userId): int
    {
        // Buscar todos los kits del usuario que contengan este prize en su JSON kit_items
        $kits = Prize::where('user_id', $userId)
            ->where('kit', 'Y')
            ->whereJsonContains('kit_items', $prizeId)
            ->get(['id', 'kit_items']);

        $updated = 0;

        foreach ($kits as $kit) {
            $itemIds = $kit->kit_items ?? [];

            if (empty($itemIds)) {
                continue;
            }

            // Obtener los precios actualizados de todos los ítems del kit
            $newKitPrice = Prize::whereIn('id', $itemIds)
                ->sum('price');

            $this->prizeRepository->update($kit->id, ['price' => (string) $newKitPrice]);
            $updated++;
        }

        return $updated;
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

    /**
     * Genera un código único de 12 caracteres alfanuméricos en mayúsculas para el usuario.
     */
    private function generateUniqueCode(string $userId): string
    {
        do {
            $code = strtoupper(Str::random(12));
        } while (Prize::where('user_id', $userId)->where('code', $code)->exists());

        return $code;
    }

    /**
     * Genera un slug único basado en el nombre del premio para el usuario.
     */
    private function generateUniqueSlug(string $name, string $userId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (Prize::where('user_id', $userId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
