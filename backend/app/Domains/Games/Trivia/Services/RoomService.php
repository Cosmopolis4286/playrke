<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Services;

use App\Domains\Games\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Games\Trivia\DTOs\PlayerDTO;
use App\Domains\Games\Trivia\DTOs\RoomDTO;
use App\Domains\Games\Trivia\Events\PlayerJoined;
use App\Domains\Games\Trivia\Events\PlayerLeft;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Servicio: Gestión de Salas de Trivia.
 * 
 * Maneja:
 * - Creación y configuración de salas
 * - Unión/salida de jugadores
 * - Estado de salas en Redis
 * - Validaciones de capacidad
 */
class RoomService implements RoomServiceInterface
{
    private const KEY_PREFIX = 'trivia:room:';
    private const TTL = 7200; // 2 horas

    public function __construct() {}

    /**
     * Crear nueva sala.
     */
    public function createRoom(array $config): RoomDTO
    {
        // Crear en base de datos
        $room = TriviaRoom::create([
            'code'               => $config['code'] ?? $this->generateRoomCode(),
            'title'              => $config['title'] ?? 'Sala de Trivia',
            'description'        => $config['description'] ?? null,
            'host_id'            => $config['host_id'] ?? null,
            'status'             => 'waiting',
            'max_players'        => $config['max_players'] ?? 100,
            'question_count'     => $config['question_count'] ?? 10,
            'time_per_question'  => $config['time_per_question'] ?? 15,
            'scheduled_at'       => $config['scheduled_at'] ?? null,
            'settings'           => $config['settings'] ?? [],
            'current_players'    => 0,
        ]);

        // Crear DTO
        $roomDto = new RoomDTO(
            roomId: (string) $room->id,
            code: $room->code,
            title: $room->title,
            status: $room->status,
            maxPlayers: $room->max_players,
            currentPlayers: 0,
            hostId: $room->host_id,
            playerIds: [],
            settings: $room->settings ?? [],
            createdAt: $room->created_at->timestamp,
            startedAt: null,
            endedAt: null
        );

        // Guardar en Redis
        $this->saveRoomToRedis($roomDto);

        return $roomDto;
    }

    /**
     * Unir jugador a sala.
     */
    public function joinRoom(string $roomId, string $playerId, array $playerData): PlayerDTO
    {
        $roomDto = $this->getRoom($roomId);

        // Validaciones
        if (!$roomDto->isJoinable()) {
            throw new RuntimeException('No se puede unir a esta sala');
        }

        if ($roomDto->isFull()) {
            throw new RuntimeException('Sala llena');
        }

        if (in_array($playerId, $roomDto->playerIds, true)) {
            throw new RuntimeException('Ya estás en esta sala');
        }

        // Crear jugador
        $player = new PlayerDTO(
            playerId: $playerId,
            roomId: $roomId,
            nickname: $playerData['nickname'] ?? 'Jugador',
            status: 'waiting',
            avatar: $playerData['avatar'] ?? null,
            joinedAt: time(),
            connectedAt: null,
            disconnectedAt: null
        );

        // Actualizar sala
        $updatedRoom = $roomDto->addPlayer($playerId);
        $this->saveRoomToRedis($updatedRoom);

        // Guardar jugador en Redis
        $this->savePlayerToRedis($player);

        // Emitir evento
        event(new PlayerJoined($player, $roomId, $updatedRoom->currentPlayers));

        return $player;
    }

    /**
     * Jugador abandona sala.
     */
    public function leaveRoom(string $roomId, string $playerId): void
    {
        $roomDto = $this->getRoom($roomId);

        if (!in_array($playerId, $roomDto->playerIds, true)) {
            return;
        }

        // Actualizar sala
        $updatedRoom = $roomDto->removePlayer($playerId);
        $this->saveRoomToRedis($updatedRoom);

        // Eliminar jugador de Redis
        $this->deletePlayerFromRedis($playerId, $roomId);

        // Emitir evento
        event(new PlayerLeft(
            playerId: $playerId,
            roomId: $roomId,
            currentPlayerCount: $updatedRoom->currentPlayers,
            reason: 'left'
        ));
    }

    /**
     * Obtener información de sala.
     */
    public function getRoom(string $roomId): RoomDTO
    {
        // Intentar desde Redis primero
        $redisData = Redis::hget(self::KEY_PREFIX . $roomId, 'data');
        
        if ($redisData) {
            $data = json_decode($redisData, true);
            if ($data) {
                return RoomDTO::fromArray($data);
            }
        }

        // Fallback a base de datos
        $room = TriviaRoom::findOrFail($roomId);

        return new RoomDTO(
            roomId: (string) $room->id,
            code: $room->code,
            title: $room->title,
            status: $room->status,
            maxPlayers: $room->max_players,
            currentPlayers: 0, // No tracking en DB
            hostId: $room->host_id,
            playerIds: [],
            settings: $room->settings ?? [],
            createdAt: $room->created_at->timestamp,
            startedAt: $room->started_at?->timestamp,
            endedAt: $room->ended_at?->timestamp
        );
    }

    /**
     * Listar jugadores en sala.
     */
    public function listPlayers(string $roomId): array
    {
        $roomDto = $this->getRoom($roomId);

        $players = [];
        foreach ($roomDto->playerIds as $playerId) {
            $playerData = Redis::hget(
                self::KEY_PREFIX . "player:{$roomId}:{$playerId}",
                'data'
            );
            if ($playerData) {
                $players[] = PlayerDTO::fromArray(json_decode($playerData, true));
            }
        }

        return $players;
    }

    /**
     * Verificar si sala está llena.
     */
    public function isRoomFull(string $roomId): bool
    {
        return $this->getRoom($roomId)->isFull();
    }

    /**
     * Cerrar sala.
     */
    public function closeRoom(string $roomId, string $reason): void
    {
        $room = TriviaRoom::findOrFail($roomId);
        $room->update(['status' => 'closed', 'ended_at' => now()]);

        // Limpiar Redis
        Redis::del(self::KEY_PREFIX . $roomId);
        Redis::del(self::KEY_PREFIX . "players:{$roomId}");
    }

    /**
     * Verificar si jugador está en sala.
     */
    public function isPlayerInRoom(string $roomId, string $playerId): bool
    {
        $roomDto = $this->getRoom($roomId);
        return in_array($playerId, $roomDto->playerIds, true);
    }

    /**
     * Actualizar configuración de sala.
     */
    public function updateRoomConfig(string $roomId, array $config): RoomDTO
    {
        $room = TriviaRoom::findOrFail($roomId);
        
        // Solo permitir si está en espera
        if ($room->status !== 'waiting') {
            throw new RuntimeException('No se puede modificar una sala activa');
        }

        $updateData = [];
        if (isset($config['title'])) {
            $updateData['title'] = $config['title'];
        }
        if (isset($config['description'])) {
            $updateData['description'] = $config['description'];
        }
        if (isset($config['max_players'])) {
            $updateData['max_players'] = $config['max_players'];
        }
        if (isset($config['settings'])) {
            $updateData['settings'] = array_merge($room->settings ?? [], $config['settings']);
        }

        $room->update($updateData);

        // Actualizar Redis
        $roomDto = $this->getRoom($roomId);
        $this->saveRoomToRedis($roomDto);

        return $roomDto;
    }

    /**
     * Generar código único de sala.
     */
    private function generateRoomCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (TriviaRoom::where('code', $code)->exists());

        return $code;
    }

    /**
     * Guardar sala en Redis.
     */
    private function saveRoomToRedis(RoomDTO $roomDto): void
    {
        $key = self::KEY_PREFIX . $roomDto->roomId;
        Redis::hset($key, 'data', json_encode($roomDto->toArray()));
        Redis::expire($key, self::TTL);
    }

    /**
     * Guardar jugador en Redis.
     */
    private function savePlayerToRedis(PlayerDTO $player): void
    {
        $key = self::KEY_PREFIX . "player:{$player->roomId}:{$player->playerId}";
        Redis::hset($key, 'data', json_encode($player->toArray()));
        Redis::expire($key, self::TTL);
    }

    /**
     * Eliminar jugador de Redis.
     */
    private function deletePlayerFromRedis(string $playerId, string $roomId): void
    {
        Redis::del(self::KEY_PREFIX . "player:{$roomId}:{$playerId}");
    }
}
