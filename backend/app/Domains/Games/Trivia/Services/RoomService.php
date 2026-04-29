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
            'category'           => $config['category'] ?? null,
            'host_id'            => $config['host_id'] ?? null,
            'status'             => 'waiting',
            'max_players'        => $config['max_players'] ?? 100,
            'question_count'     => $config['question_count'] ?? 10,
            'time_per_question'  => $config['time_per_question'] ?? 15,
            'entry_fee'          => $config['entry_fee'] ?? 0,
            'entry_currency_id'  => $config['entry_currency_id'] ?? null,
            'prize_type'         => $config['prize_type'] ?? 'accumulated',
            'prize_amount'       => $config['prize_amount'] ?? 0,
            'prize_currency_id'  => $config['prize_currency_id'] ?? null,
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

        // Emitir evento (best-effort — puede fallar si broadcasting usa Redis)
        try {
            event(new PlayerJoined($player, $roomId, $updatedRoom->currentPlayers));
        } catch (\Throwable) {
            // Redis/broadcasting no disponible — ignorar
        }

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

        // Emitir evento (best-effort)
        try {
            event(new PlayerLeft(
                playerId: $playerId,
                roomId: $roomId,
                currentPlayerCount: $updatedRoom->currentPlayers,
                reason: 'left'
            ));
        } catch (\Throwable) {
            // Redis/broadcasting no disponible — ignorar
        }
    }

    /**
     * Obtener información de sala.
     * Redis es opcional — si no está disponible cae a base de datos.
     */
    public function getRoom(string $roomId): RoomDTO
    {
        // Intentar desde Redis primero
        try {
            $redisData = Redis::hget(self::KEY_PREFIX . $roomId, 'data');

            if ($redisData) {
                $data = json_decode($redisData, true);
                if ($data) {
                    return RoomDTO::fromArray($data);
                }
            }
        } catch (\Throwable) {
            // Redis no disponible — continuar con fallback a DB
        }

        // Fallback a base de datos
        $room = TriviaRoom::findOrFail($roomId);

        return new RoomDTO(
            roomId: (string) $room->id,
            code: $room->code,
            title: $room->title,
            status: $room->status,
            maxPlayers: $room->max_players,
            currentPlayers: $room->current_players ?? 0,
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
     * Devuelve array vacío si Redis no está disponible.
     */
    public function listPlayers(string $roomId): array
    {
        $roomDto = $this->getRoom($roomId);

        $players = [];
        foreach ($roomDto->playerIds as $playerId) {
            try {
                $playerData = Redis::hget(
                    self::KEY_PREFIX . "player:{$roomId}:{$playerId}",
                    'data'
                );
                if ($playerData) {
                    $players[] = PlayerDTO::fromArray(json_decode($playerData, true));
                }
            } catch (\Throwable) {
                // Redis no disponible — saltar jugador
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

        // Limpiar Redis (opcional — no falla si Redis no está disponible)
        try {
            Redis::del(self::KEY_PREFIX . $roomId);
            Redis::del(self::KEY_PREFIX . "players:{$roomId}");
        } catch (\Throwable) {
            // Redis no disponible — ignorar limpieza
        }
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
        if (array_key_exists('category', $config)) {
            $updateData['category'] = $config['category'];
        }
        if (isset($config['max_players'])) {
            $updateData['max_players'] = $config['max_players'];
        }
        if (array_key_exists('entry_fee', $config)) {
            $updateData['entry_fee'] = $config['entry_fee'];
        }
        if (array_key_exists('entry_currency_id', $config)) {
            $updateData['entry_currency_id'] = $config['entry_currency_id'];
        }
        if (array_key_exists('prize_type', $config)) {
            $updateData['prize_type'] = $config['prize_type'];
        }
        if (array_key_exists('prize_amount', $config)) {
            $updateData['prize_amount'] = $config['prize_amount'];
        }
        if (array_key_exists('prize_currency_id', $config)) {
            $updateData['prize_currency_id'] = $config['prize_currency_id'];
        }
        if (array_key_exists('scheduled_at', $config)) {
            $updateData['scheduled_at'] = $config['scheduled_at'];
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
     * Guardar sala en Redis (best-effort).
     */
    private function saveRoomToRedis(RoomDTO $roomDto): void
    {
        try {
            $key = self::KEY_PREFIX . $roomDto->roomId;
            Redis::hset($key, 'data', json_encode($roomDto->toArray()));
            Redis::expire($key, self::TTL);
        } catch (\Throwable) {
            // Redis no disponible — ignorar
        }
    }

    /**
     * Guardar jugador en Redis (best-effort).
     */
    private function savePlayerToRedis(PlayerDTO $player): void
    {
        try {
            $key = self::KEY_PREFIX . "player:{$player->roomId}:{$player->playerId}";
            Redis::hset($key, 'data', json_encode($player->toArray()));
            Redis::expire($key, self::TTL);
        } catch (\Throwable) {
            // Redis no disponible — ignorar
        }
    }

    /**
     * Eliminar jugador de Redis (best-effort).
     */
    private function deletePlayerFromRedis(string $playerId, string $roomId): void
    {
        try {
            Redis::del(self::KEY_PREFIX . "player:{$roomId}:{$playerId}");
        } catch (\Throwable) {
            // Redis no disponible — ignorar
        }
    }
}
