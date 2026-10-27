<?php

namespace App\Services\Games\Common\GameMode;

use App\Contracts\Games\Common\GameMode\GameModeRepositoryInterface;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Models\Games\Common\GameMode\GameMode;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

final class GameModeService
{
    public function __construct(
        private readonly GameModeRepositoryInterface $gameModes,
    ) {
    }

    public function create(array $data) : GameMode
    {
        $code = strtoupper($data['code']);

        if ($this->gameModes->existsByGameAndCode($data['game_id'], $code)) {
            throw new InvalidArgumentException("Ya existe un modo con código '{$code}' para este juego.");
        }

        return $this->gameModes->create([
            'game_id' => $data['game_id'],
            'code' => $code,
            'label' => $data['label'],
            'status' => $data['status'] ?? 'ACTIVE',
        ]);
    }

    public function delete(string $gameModeId) : bool
    {
        // trivia_rooms vive en Postgres (pgsql_trivia): game_mode_id ahí es
        // una referencia blanda sin FK física, así que hay que validar el
        // uso manualmente antes de borrar — un FK real (MySQL, ej. Bingo)
        // ya se protegería solo vía restrictOnDelete si existiera.
        if (TriviaRoom::where('game_mode_id', $gameModeId)->exists()) {
            throw new InvalidArgumentException('No se puede eliminar: hay salas de Trivia usando este modo.');
        }

        // bingo_rooms_config.game_mode_id sí tiene FK física (MySQL, misma conexión),
        // pero es nullOnDelete (el modo es opcional) — sin este guard, borrar un modo
        // en uso dejaría salas de Bingo con game_mode_id vaciado silenciosamente.
        if (BingoRoomConfig::where('game_mode_id', $gameModeId)->exists()) {
            throw new InvalidArgumentException('No se puede eliminar: hay salas de Bingo usando este modo.');
        }

        if (BingoRoomAutomatic::where('game_mode_id', $gameModeId)->exists()) {
            throw new InvalidArgumentException('No se puede eliminar: hay salas automáticas de Bingo usando este modo.');
        }

        return $this->gameModes->delete($gameModeId);
    }

    public function find(string $gameModeId) : GameMode
    {
        /** @var GameMode */
        return $this->gameModes->findOrFail($gameModeId);
    }

    public function listByGame(?string $gameId = null) : Collection
    {
        return $gameId ? $this->gameModes->getByGame($gameId) : $this->gameModes->all();
    }

    public function toggleStatus(string $gameModeId) : GameMode
    {
        /** @var GameMode $gameMode */
        $gameMode = $this->gameModes->findOrFail($gameModeId);

        return $this->gameModes->update($gameModeId, [
            'status' => $gameMode->isActive() ? 'INACTIVE' : 'ACTIVE',
        ]);
    }

    public function update(string $gameModeId, array $data) : GameMode
    {
        /** @var GameMode $gameMode */
        $gameMode = $this->gameModes->findOrFail($gameModeId);
        $code = strtoupper($data['code']);

        if ($this->gameModes->existsByGameAndCode($gameMode->game_id, $code, $gameModeId)) {
            throw new InvalidArgumentException("Ya existe un modo con código '{$code}' para este juego.");
        }

        return $this->gameModes->update($gameModeId, [
            'code' => $code,
            'label' => $data['label'],
            'status' => $data['status'] ?? $gameMode->status,
        ]);
    }
}
