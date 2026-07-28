<?php

namespace App\Services\Games\Common\GameMode;

use App\Contracts\Games\Common\GameMode\GameModeRepositoryInterface;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Models\Games\Common\GameMode\GameMode;
use Illuminate\Database\Eloquent\Collection;

final class GameModeService
{
    public function __construct(
        private readonly GameModeRepositoryInterface $gameModes,
    ) {}

    public function listByGame(?string $gameId = null): Collection
    {
        return $gameId ? $this->gameModes->getByGame($gameId) : $this->gameModes->all();
    }

    public function find(string $gameModeId): GameMode
    {
        /** @var GameMode */
        return $this->gameModes->findOrFail($gameModeId);
    }

    public function create(array $data): GameMode
    {
        $code = strtoupper($data['code']);

        if ($this->gameModes->existsByGameAndCode($data['game_id'], $code)) {
            throw new \InvalidArgumentException("Ya existe un modo con código '{$code}' para este juego.");
        }

        return $this->gameModes->create([
            'game_id' => $data['game_id'],
            'code' => $code,
            'label' => $data['label'],
            'status' => $data['status'] ?? 'ACTIVE',
        ]);
    }

    public function update(string $gameModeId, array $data): GameMode
    {
        /** @var GameMode $gameMode */
        $gameMode = $this->gameModes->findOrFail($gameModeId);
        $code = strtoupper($data['code']);

        if ($this->gameModes->existsByGameAndCode($gameMode->game_id, $code, $gameModeId)) {
            throw new \InvalidArgumentException("Ya existe un modo con código '{$code}' para este juego.");
        }

        return $this->gameModes->update($gameModeId, [
            'code' => $code,
            'label' => $data['label'],
            'status' => $data['status'] ?? $gameMode->status,
        ]);
    }

    public function toggleStatus(string $gameModeId): GameMode
    {
        /** @var GameMode $gameMode */
        $gameMode = $this->gameModes->findOrFail($gameModeId);

        return $this->gameModes->update($gameModeId, [
            'status' => $gameMode->isActive() ? 'INACTIVE' : 'ACTIVE',
        ]);
    }

    public function delete(string $gameModeId): bool
    {
        // trivia_rooms vive en Postgres (pgsql_trivia): game_mode_id ahí es
        // una referencia blanda sin FK física, así que hay que validar el
        // uso manualmente antes de borrar — un FK real (MySQL, ej. Bingo)
        // ya se protegería solo vía restrictOnDelete si existiera.
        if (TriviaRoom::where('game_mode_id', $gameModeId)->exists()) {
            throw new \InvalidArgumentException('No se puede eliminar: hay salas de Trivia usando este modo.');
        }

        return $this->gameModes->delete($gameModeId);
    }
}
