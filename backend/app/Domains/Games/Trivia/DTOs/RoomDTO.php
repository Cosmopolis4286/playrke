<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\DTOs;

/**
 * DTO para salas de trivia.
 */
class RoomDTO
{
    /**
     * @param array<string> $playerIds IDs de jugadores en la sala
     * @param array<string, mixed> $settings Configuración de la sala
     */
    public function __construct(
        public readonly string $roomId,
        public readonly string $code,
        public readonly string $title,
        public readonly string $status,
        public readonly int $maxPlayers,
        public readonly int $currentPlayers,
        public readonly ?string $hostId,
        public readonly array $playerIds = [],
        public readonly array $settings = [],
        public readonly ?int $createdAt = null,
        public readonly ?int $startedAt = null,
        public readonly ?int $endedAt = null
    ) {}

    /**
     * Crear desde array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            roomId: $data['room_id'],
            code: $data['code'],
            title: $data['title'],
            status: $data['status'],
            maxPlayers: $data['max_players'] ?? 100,
            currentPlayers: $data['current_players'] ?? 0,
            hostId: $data['host_id'] ?? null,
            playerIds: $data['player_ids'] ?? [],
            settings: $data['settings'] ?? [],
            createdAt: $data['created_at'] ?? time(),
            startedAt: $data['started_at'] ?? null,
            endedAt: $data['ended_at'] ?? null
        );
    }

    /**
     * Convertir a array.
     */
    public function toArray(): array
    {
        return [
            'room_id' => $this->roomId,
            'code' => $this->code,
            'title' => $this->title,
            'status' => $this->status,
            'max_players' => $this->maxPlayers,
            'current_players' => $this->currentPlayers,
            'host_id' => $this->hostId,
            'player_ids' => $this->playerIds,
            'settings' => $this->settings,
            'created_at' => $this->createdAt,
            'started_at' => $this->startedAt,
            'ended_at' => $this->endedAt,
        ];
    }

    /**
     * Verificar si la sala está llena.
     */
    public function isFull(): bool
    {
        return $this->currentPlayers >= $this->maxPlayers;
    }

    /**
     * Verificar si la sala está disponible para unirse.
     */
    public function isJoinable(): bool
    {
        return $this->status === 'waiting' && !$this->isFull();
    }

    /**
     * Agregar jugador a la sala.
     */
    public function addPlayer(string $playerId): self
    {
        if (in_array($playerId, $this->playerIds, true)) {
            return $this;
        }

        $newPlayerIds = [...$this->playerIds, $playerId];

        return new self(
            roomId: $this->roomId,
            code: $this->code,
            title: $this->title,
            status: $this->status,
            maxPlayers: $this->maxPlayers,
            currentPlayers: count($newPlayerIds),
            hostId: $this->hostId,
            playerIds: $newPlayerIds,
            settings: $this->settings,
            createdAt: $this->createdAt,
            startedAt: $this->startedAt,
            endedAt: $this->endedAt
        );
    }

    /**
     * Remover jugador de la sala.
     */
    public function removePlayer(string $playerId): self
    {
        $newPlayerIds = array_values(array_filter(
            $this->playerIds,
            fn($id) => $id !== $playerId
        ));

        return new self(
            roomId: $this->roomId,
            code: $this->code,
            title: $this->title,
            status: $this->status,
            maxPlayers: $this->maxPlayers,
            currentPlayers: count($newPlayerIds),
            hostId: $this->hostId,
            playerIds: $newPlayerIds,
            settings: $this->settings,
            createdAt: $this->createdAt,
            startedAt: $this->startedAt,
            endedAt: $this->endedAt
        );
    }

    /**
     * Cambiar estado de la sala.
     */
    public function withStatus(string $status): self
    {
        return new self(
            roomId: $this->roomId,
            code: $this->code,
            title: $this->title,
            status: $status,
            maxPlayers: $this->maxPlayers,
            currentPlayers: $this->currentPlayers,
            hostId: $this->hostId,
            playerIds: $this->playerIds,
            settings: $this->settings,
            createdAt: $this->createdAt,
            startedAt: $status === 'playing' ? time() : $this->startedAt,
            endedAt: $status === 'closed' ? time() : $this->endedAt
        );
    }
}
