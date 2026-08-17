<?php

namespace App\Services\Games\Common\Jackpot;

use App\Contracts\Games\Common\Jackpot\JackpotTypeRepositoryInterface;
use App\Contracts\JackpotRepositoryInterface;
use App\Models\Games\Common\Jackpot\JackpotType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class JackpotTypeService
{
    public function __construct(
        private readonly JackpotTypeRepositoryInterface $jackpotTypes,
        private readonly JackpotRepositoryInterface $jackpots,
    ) {}

    /**
     * Lista los tipos de jackpot, enriquecidos con el saldo actual del pozo
     * (current_amount, atributo dinámico sin columna propia) para mostrarse
     * de solo lectura en el panel admin.
     */
    public function listByGame(?string $gameId = null): Collection
    {
        $types = $gameId ? $this->jackpotTypes->getByGame($gameId) : $this->jackpotTypes->all();

        return $types->each(function (JackpotType $type) {
            $type->setAttribute('current_amount', $this->jackpots->findByType((string) $type->id)?->amount ?? 0.0);
        });
    }

    public function find(string $id): JackpotType
    {
        /** @var JackpotType */
        return $this->jackpotTypes->findOrFail($id);
    }

    public function create(array $data): JackpotType
    {
        $code = strtoupper($data['code']);

        if ($this->jackpotTypes->existsByGameAndCode($data['game_id'], $code)) {
            throw new \InvalidArgumentException("Ya existe un jackpot con código '{$code}' para este juego.");
        }

        return $this->jackpotTypes->create([
            'game_id' => $data['game_id'],
            'currency_id' => $data['currency_id'],
            'code' => $code,
            'label' => $data['label'],
            'periodicity' => $data['periodicity'] ?? JackpotType::PERIODICITY_PER_ROOM,
            'status' => $data['status'] ?? 'ACTIVE',
        ]);
    }

    public function update(string $id, array $data): JackpotType
    {
        /** @var JackpotType $jackpotType */
        $jackpotType = $this->jackpotTypes->findOrFail($id);
        $code = strtoupper($data['code']);

        if ($this->jackpotTypes->existsByGameAndCode($jackpotType->game_id, $code, $id)) {
            throw new \InvalidArgumentException("Ya existe un jackpot con código '{$code}' para este juego.");
        }

        return $this->jackpotTypes->update($id, [
            'code' => $code,
            'label' => $data['label'],
            'periodicity' => $data['periodicity'] ?? $jackpotType->periodicity,
            'status' => $data['status'] ?? $jackpotType->status,
        ]);
    }

    public function toggleStatus(string $id): JackpotType
    {
        /** @var JackpotType $jackpotType */
        $jackpotType = $this->jackpotTypes->findOrFail($id);

        return $this->jackpotTypes->update($id, [
            'status' => $jackpotType->isActive() ? 'INACTIVE' : 'ACTIVE',
        ]);
    }

    public function delete(string $id): bool
    {
        // FK real (nullOnDelete) en bingo_rooms_config/bingo_room_templates
        // /bingo_rooms_automatic no bloquea el borrado a nivel de BD (el
        // jackpot es opcional en la sala), así que esta validación es la
        // única barrera real contra dejar salas apuntando a un jackpot
        // borrado con aportes históricos huérfanos.
        $inUse = DB::table('bingo_rooms_config')->where('jackpot_type_id', $id)->exists()
            || DB::table('bingo_rooms_automatic')->where('jackpot_type_id', $id)->exists()
            || DB::table('bingo_room_templates')->where('jackpot_type_id', $id)->exists();

        if ($inUse) {
            throw new \InvalidArgumentException('No se puede eliminar: hay salas de Bingo usando este jackpot. Desactívalo en su lugar.');
        }

        return $this->jackpotTypes->delete($id);
    }
}
