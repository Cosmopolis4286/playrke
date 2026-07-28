<?php

namespace App\Services\Games\Common\PrizePool;

use App\Contracts\Games\Common\PrizePool\PrizePoolTypeRepositoryInterface;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class PrizePoolTypeService
{
    public function __construct(
        private readonly PrizePoolTypeRepositoryInterface $prizePoolTypes,
    ) {}

    public function listByGame(?string $gameId = null): Collection
    {
        return $gameId ? $this->prizePoolTypes->getByGame($gameId) : $this->prizePoolTypes->all();
    }

    public function find(string $id): PrizePoolType
    {
        /** @var PrizePoolType */
        return $this->prizePoolTypes->findOrFail($id);
    }

    public function create(array $data): PrizePoolType
    {
        $code = strtoupper($data['code']);

        if ($this->prizePoolTypes->existsByGameAndCode($data['game_id'], $code)) {
            throw new \InvalidArgumentException("Ya existe un tipo de bote con código '{$code}' para este juego.");
        }

        return $this->prizePoolTypes->create([
            'game_id' => $data['game_id'],
            'code' => $code,
            'label' => $data['label'],
            'status' => $data['status'] ?? 'ACTIVE',
        ]);
    }

    public function update(string $id, array $data): PrizePoolType
    {
        /** @var PrizePoolType $prizePoolType */
        $prizePoolType = $this->prizePoolTypes->findOrFail($id);
        $code = strtoupper($data['code']);

        if ($this->prizePoolTypes->existsByGameAndCode($prizePoolType->game_id, $code, $id)) {
            throw new \InvalidArgumentException("Ya existe un tipo de bote con código '{$code}' para este juego.");
        }

        return $this->prizePoolTypes->update($id, [
            'code' => $code,
            'label' => $data['label'],
            'status' => $data['status'] ?? $prizePoolType->status,
        ]);
    }

    public function toggleStatus(string $id): PrizePoolType
    {
        /** @var PrizePoolType $prizePoolType */
        $prizePoolType = $this->prizePoolTypes->findOrFail($id);

        return $this->prizePoolTypes->update($id, [
            'status' => $prizePoolType->isActive() ? 'INACTIVE' : 'ACTIVE',
        ]);
    }

    public function delete(string $id): bool
    {
        // FK real (restrictOnDelete) en bingo_rooms_config/bingo_room_templates
        // /bingo_rooms_automatic ya protege el borrado a nivel de BD, pero se
        // valida aquí también para dar un mensaje claro en vez de un 500.
        $inUse = DB::table('bingo_rooms_config')->where('prize_pool_type_id', $id)->exists()
            || DB::table('bingo_rooms_automatic')->where('prize_pool_type_id', $id)->exists()
            || DB::table('bingo_room_templates')->where('prize_pool_type_id', $id)->exists();

        if ($inUse) {
            throw new \InvalidArgumentException('No se puede eliminar: hay salas de Bingo usando este tipo de bote. Desactívalo en su lugar.');
        }

        return $this->prizePoolTypes->delete($id);
    }
}
