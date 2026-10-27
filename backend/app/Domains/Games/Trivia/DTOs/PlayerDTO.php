<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\DTOs;

/**
 * DTO para jugadores de trivia.
 */
class PlayerDTO
{
    public static function fromArray(array $data) : self
    {
        return new self(
            playerId: $data['player_id'],
            roomId: $data['room_id'],
            nickname: $data['nickname'],
            status: $data['status'],
            avatar: $data['avatar'] ?? null,
            joinedAt: $data['joined_at'] ?? time(),
            connectedAt: $data['connected_at'] ?? null,
            disconnectedAt: $data['disconnected_at'] ?? null,
        );
    }

    public function __construct(
        public readonly string $playerId,
        public readonly string $roomId,
        public readonly string $nickname,
        public readonly string $status, // 'waiting', 'playing', 'disconnected', 'finished'
        public readonly ?string $avatar = null,
        public readonly int $joinedAt = 0,
        public readonly ?int $connectedAt = null,
        public readonly ?int $disconnectedAt = null,
    ) {
    }

    public function toArray() : array
    {
        return [
            'player_id' => $this->playerId,
            'room_id' => $this->roomId,
            'nickname' => $this->nickname,
            'status' => $this->status,
            'avatar' => $this->avatar,
            'joined_at' => $this->joinedAt,
            'connected_at' => $this->connectedAt,
            'disconnected_at' => $this->disconnectedAt,
        ];
    }

    public function withStatus(string $status) : self
    {
        return new self(
            playerId: $this->playerId,
            roomId: $this->roomId,
            nickname: $this->nickname,
            status: $status,
            avatar: $this->avatar,
            joinedAt: $this->joinedAt,
            connectedAt: $status === 'playing' ? time() : $this->connectedAt,
            disconnectedAt: $status === 'disconnected' ? time() : $this->disconnectedAt,
        );
    }
}
