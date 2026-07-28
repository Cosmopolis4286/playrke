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

    public function listByGame(string $gameId): Collection
    {
        return $this->gameModes->getByGame($gameId);
    }

    public function create(string $gameId, string $code, string $label): GameMode
    {
        $code = strtoupper($code);

        if ($this->gameModes->existsByGameAndCode($gameId, $code)) {
            throw new \InvalidArgumentException("Ya existe un modo con código '{$code}' para este juego.");
        }

        return $this->gameModes->create([
            'game_id' => $gameId,
            'code' => $code,
            'label' => $label,
            'status' => 'ACTIVE',
        ]);
    }

    public function update(string $gameModeId, string $code, string $label): GameMode
    {
        /** @var GameMode $gameMode */
        $gameMode = $this->gameModes->findOrFail($gameModeId);
        $code = strtoupper($code);

        if ($this->gameModes->existsByGameAndCode($gameMode->game_id, $code, $gameModeId)) {
            throw new \InvalidArgumentException("Ya existe un modo con código '{$code}' para este juego.");
        }

        return $this->gameModes->update($gameModeId, [
            'code' => $code,
            'label' => $label,
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
