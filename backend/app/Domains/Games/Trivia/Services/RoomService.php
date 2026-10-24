<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Services;

use App\Domains\Games\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Games\Trivia\DTOs\PlayerDTO;
use App\Domains\Games\Trivia\DTOs\RoomDTO;
use App\Domains\Games\Trivia\Exceptions\Room\TriviaRoomException;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Models\TriviaTeam;
use App\Events\Games\Trivia\TriviaPlayerJoined;
use App\Events\Games\Trivia\TriviaPlayerLeft;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

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

    public function __construct()
    {
    }

    /**
     * Cerrar sala.
     */
    public function closeRoom(string $roomId, string $reason) : void
    {
        $room = TriviaRoom::findOrFail($roomId);
        $room->update(['status' => 'closed', 'ended_at' => now()]);

        // Limpiar Redis (opcional — no falla si Redis no está disponible)
        try {
            Redis::del(self::KEY_PREFIX . $roomId);
            // "trivia:room:players:{roomId}" nunca fue escrita — se elimina solo la clave real.
        } catch (Throwable) {
            // Redis no disponible — ignorar limpieza
        }
    }

    /**
     * Crear nueva sala.
     */
    public function createRoom(array $config) : RoomDTO
    {
        $gameModeId = $config['game_mode_id'] ?? null;
        $teamsConfig = $config['teams_config'] ?? null;

        // Crear en base de datos
        $room = TriviaRoom::create([
            'code' => $config['code'] ?? $this->generateRoomCode(),
            'title' => $config['title'] ?? 'Sala de Trivia',
            'description' => $config['description'] ?? null,
            'category' => $config['category'] ?? null,
            'host_id' => $config['host_id'] ?? null,
            'status' => 'waiting',
            'game_mode_id' => $gameModeId,
            'teams_config' => $teamsConfig,
            'max_players' => $config['max_players'] ?? 100,
            'question_count' => $config['question_count'] ?? 10,
            'time_per_question' => $config['time_per_question'] ?? 15,
            'entry_fee' => $config['entry_fee'] ?? 0,
            'entry_currency_id' => $config['entry_currency_id'] ?? null,
            'fee_by_currency' => $config['fee_by_currency'] ?? null,
            'prize_type' => $config['prize_type'] ?? 'accumulated',
            'prize_amount' => $config['prize_amount'] ?? 0,
            'prize_currency_id' => $config['prize_currency_id'] ?? null,
            'prize_model' => $config['prize_model'] ?? 'guaranteed',
            'prize_payouts' => $config['prize_payouts'] ?? null,
            'platform_rake_percent' => $config['platform_rake_percent'] ?? 0,
            'sponsor_prize' => $config['sponsor_prize'] ?? 0,
            'scheduled_at' => $config['scheduled_at'] ?? null,
            'settings' => $config['settings'] ?? [],
            'current_players' => 0,
        ]);

        // Crear equipos si el modo lo requiere
        if ($room->usesTeams() && $teamsConfig) {
            $this->createTeamsForRoom($room, $teamsConfig);
        }

        // Recargar con equipos
        $room->load('teams');

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
            settings: array_merge($room->settings ?? [], [
                'game_mode_id' => $room->game_mode_id,
                'teams' => $room->teams->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'color' => $t->color,
                    'icon' => $t->icon,
                ])->toArray(),
            ]),
            createdAt: $room->created_at->timestamp,
            startedAt: null,
            endedAt: null,
        );

        // Guardar en Redis
        $this->saveRoomToRedis($roomDto);

        return $roomDto;
    }

    /**
     * Obtener información de sala.
     * Redis es opcional — si no está disponible cae a base de datos.
     */
    public function getRoom(string $roomId) : RoomDTO
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
        } catch (Throwable) {
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
            endedAt: $room->ended_at?->timestamp,
        );
    }

    /**
     * Verificar si jugador está en sala.
     */
    public function isPlayerInRoom(string $roomId, string $playerId) : bool
    {
        $roomDto = $this->getRoom($roomId);

        return in_array($playerId, $roomDto->playerIds, true);
    }

    /**
     * Verificar si sala está llena.
     */
    public function isRoomFull(string $roomId) : bool
    {
        return $this->getRoom($roomId)->isFull();
    }

    /**
     * Unir jugador a sala.
     * Actualiza Redis y sincroniza current_players en BD.
     */
    public function joinRoom(string $roomId, string $playerId, array $playerData) : PlayerDTO
    {
        $roomDto = $this->getRoom($roomId);

        if (! $roomDto->isJoinable()) {
            throw TriviaRoomException::notJoinable();
        }

        if ($roomDto->isFull()) {
            throw TriviaRoomException::roomFull();
        }

        if (in_array($playerId, $roomDto->playerIds, true)) {
            // Jugador ya en Redis — devolver sin error
            return new PlayerDTO(
                playerId: $playerId,
                roomId: $roomId,
                nickname: $playerData['nickname'] ?? 'Jugador',
                status: 'waiting',
                avatar: null,
                joinedAt: time(),
                connectedAt: null,
                disconnectedAt: null,
            );
        }

        $player = new PlayerDTO(
            playerId: $playerId,
            roomId: $roomId,
            nickname: $playerData['nickname'] ?? 'Jugador',
            status: 'waiting',
            avatar: $playerData['avatar'] ?? null,
            joinedAt: time(),
            connectedAt: null,
            disconnectedAt: null,
        );

        // Actualizar Redis
        $updatedRoom = $roomDto->addPlayer($playerId);
        $this->saveRoomToRedis($updatedRoom);
        $this->savePlayerToRedis($player);

        // Sincronizar current_players en BD (best-effort)
        try {
            TriviaRoom::where('id', $roomId)
                ->update(['current_players' => $updatedRoom->currentPlayers]);
        } catch (Throwable) {
        }

        // Broadcast del nuevo evento (best-effort)
        try {
            broadcast(new TriviaPlayerJoined(
                roomId: $roomId,
                playerId: $playerId,
                playerName: $player->nickname,
                currentPlayerCount: $updatedRoom->currentPlayers,
                teamId: $playerData['team_id'] ?? null,
            ));
        } catch (Throwable) {
        }

        return $player;
    }

    /**
     * Jugador abandona sala.
     */
    public function leaveRoom(string $roomId, string $playerId) : void
    {
        // Persistir primero la salida: Redis es una proyección en tiempo real,
        // no la fuente de verdad para decidir si el participante sigue inscrito.
        $roomStatus = null;
        $activeCount = null;
        $hasPersistedParticipant = false;

        DB::connection('pgsql_trivia')->transaction(function () use ($roomId, $playerId, &$roomStatus, &$activeCount, &$hasPersistedParticipant) {
            $room = DB::connection('pgsql_trivia')
                ->table('trivia_rooms')
                ->where('id', $roomId)
                ->lockForUpdate()
                ->first();

            if (! $room) {
                return;
            }

            $roomStatus = (string) $room->status;

            // Algunas rutas heredadas solo mantienen jugadores en Redis. Si
            // existe una inscripción persistida, PostgreSQL gobierna su salida
            // y el contador; si no, conservamos el comportamiento legacy.
            $hasPersistedParticipant = DB::connection('pgsql_trivia')
                ->table('trivia_participants')
                ->where('room_id', $roomId)
                ->where('user_id', $playerId)
                ->whereNull('deleted_at')
                ->exists();

            // Una salida del lobby abandona la inscripción. No cambiar el
            // estado de participantes durante una partida: el motor necesita
            // conservar su snapshot para resolver resultados y premios.
            if ($roomStatus === 'waiting' && $hasPersistedParticipant) {
                DB::connection('pgsql_trivia')
                    ->table('trivia_participants')
                    ->where('room_id', $roomId)
                    ->where('user_id', $playerId)
                    ->whereNull('deleted_at')
                    ->whereNotIn('status', ['abandoned'])
                    ->update([
                        'status' => 'abandoned',
                        'updated_at' => now(),
                    ]);

                $activeCount = DB::connection('pgsql_trivia')
                    ->table('trivia_participants')
                    ->where('room_id', $roomId)
                    ->whereNull('deleted_at')
                    ->whereNotIn('status', ['abandoned'])
                    ->count();

                DB::connection('pgsql_trivia')
                    ->table('trivia_rooms')
                    ->where('id', $roomId)
                    ->update(['current_players' => $activeCount]);
            }
        });

        // Actualizar Redis aunque el jugador falte en su lista (caché obsoleta).
        // No retornar temprano basándose únicamente en playerIds cacheados.
        try {
            $roomDto = $this->getRoom($roomId);
            $wasCached = in_array($playerId, $roomDto->playerIds, true);

            if ($wasCached) {
                $updatedRoom = $roomDto->removePlayer($playerId);
                $this->saveRoomToRedis($updatedRoom);
            } else {
                $updatedRoom = $roomDto;
            }

            $this->deletePlayerFromRedis($playerId, $roomId);

            if ($activeCount !== null) {
                // El DTO puede estar desactualizado; publicar el conteo confirmado
                // por PostgreSQL para evitar que el lobby conserve el valor viejo.
                $updatedRoom = new RoomDTO(
                    roomId: $roomDto->roomId,
                    code: $roomDto->code,
                    title: $roomDto->title,
                    status: $roomDto->status,
                    maxPlayers: $roomDto->maxPlayers,
                    currentPlayers: $activeCount,
                    hostId: $roomDto->hostId,
                    playerIds: array_values(array_filter(
                        $roomDto->playerIds,
                        fn ($id) => (string) $id !== $playerId
                    )),
                    settings: $roomDto->settings,
                    createdAt: $roomDto->createdAt,
                    startedAt: $roomDto->startedAt,
                    endedAt: $roomDto->endedAt,
                );
                $this->saveRoomToRedis($updatedRoom);
            }
        } catch (Throwable) {
            // La salida ya quedó persistida; Redis se reconstruye al próximo acceso.
        }

        try {
            broadcast(new TriviaPlayerLeft(
                roomId: $roomId,
                playerId: $playerId,
                currentPlayerCount: $activeCount ?? max(0, ($updatedRoom->currentPlayers ?? 0)),
                reason: 'left',
            ));
        } catch (Throwable) {
        }
    }

    /**
     * Listar jugadores en sala.
     * Intenta Redis primero; si no hay datos hace fallback a trivia_participants en BD.
     */
    public function listPlayers(string $roomId) : array
    {
        $roomDto = $this->getRoom($roomId);
        $players = [];

        // Intentar desde Redis
        foreach ($roomDto->playerIds as $playerId) {
            try {
                $raw = Redis::hget(self::KEY_PREFIX . "player:{$roomId}:{$playerId}", 'data');
                if ($raw) {
                    $players[] = PlayerDTO::fromArray(json_decode($raw, true));
                }
            } catch (Throwable) {
            }
        }

        // Si Redis no tenía datos, cargar desde BD
        if (empty($players)) {
            $participants = DB::connection('pgsql_trivia')
                ->table('trivia_participants')
                ->where('room_id', $roomId)
                ->whereNull('deleted_at')
                ->whereNotIn('status', ['abandoned'])
                ->select('user_id', 'status', 'registered_at')
                ->get();

            $userIds = $participants->pluck('user_id')->all();
            $names = $userIds
                ? DB::table('users')
                    ->whereIn('id', $userIds)
                    ->select('id', DB::raw("TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as display_name"))
                    ->get()->pluck('display_name', 'id')->all()
                : [];

            foreach ($participants as $p) {
                $players[] = new PlayerDTO(
                    playerId: $p->user_id,
                    roomId: $roomId,
                    nickname: $names[$p->user_id] ?? 'Jugador',
                    status: $p->status,
                    avatar: null,
                    joinedAt: $p->registered_at ? strtotime($p->registered_at) : time(),
                    connectedAt: null,
                    disconnectedAt: null,
                );
            }
        }

        return $players;
    }

    /**
     * Actualizar configuración de sala.
     */
    public function updateRoomConfig(string $roomId, array $config) : RoomDTO
    {
        $room = TriviaRoom::findOrFail($roomId);

        // Solo permitir si está en espera
        if ($room->status !== 'waiting') {
            throw TriviaRoomException::notEditableWhileActive();
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
        if (isset($config['game_mode_id'])) {
            $updateData['game_mode_id'] = $config['game_mode_id'];
        }
        if (isset($config['teams_config'])) {
            $updateData['teams_config'] = $config['teams_config'];
            // Recrear equipos si cambió la config
            if ($room->usesTeams()) {
                $room->teams()->delete();
                $this->createTeamsForRoom($room, $config['teams_config']);
            }
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
        if (array_key_exists('fee_by_currency', $config)) {
            $updateData['fee_by_currency'] = $config['fee_by_currency'];
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
        if (array_key_exists('prize_model', $config)) {
            $updateData['prize_model'] = $config['prize_model'];
        }
        if (array_key_exists('prize_payouts', $config)) {
            $updateData['prize_payouts'] = $config['prize_payouts'];
        }
        if (array_key_exists('platform_rake_percent', $config)) {
            $updateData['platform_rake_percent'] = $config['platform_rake_percent'];
        }
        if (array_key_exists('sponsor_prize', $config)) {
            $updateData['sponsor_prize'] = $config['sponsor_prize'];
        }
        if (array_key_exists('scheduled_at', $config)) {
            $updateData['scheduled_at'] = $config['scheduled_at'];
        }
        if (isset($config['settings'])) {
            $updateData['settings'] = array_merge($room->settings ?? [], $config['settings']);
        }

        $room->update($updateData);

        // Invalidar el cache antes de releer: getRoom() prioriza Redis, que
        // en este punto todavía tiene el DTO de antes del update — sin este
        // borrado, getRoom() devuelve (y vuelve a guardar) el valor viejo,
        // dejando la actualización invisible para cualquiera que lea vía Redis.
        try {
            Redis::del(self::KEY_PREFIX . $roomId);
        } catch (Throwable) {
            // Redis no disponible — getRoom() caerá al fallback de BD de todas formas.
        }

        $roomDto = $this->getRoom($roomId);
        $this->saveRoomToRedis($roomDto);

        return $roomDto;
    }

    /**
     * Crear equipos para una sala según la configuración.
     */
    private function createTeamsForRoom(TriviaRoom $room, array $config) : void
    {
        $mode = strtolower($room->gameModeCode() ?? '');
        $teamCount = $config['team_count'] ?? 2;
        $teamSize = $config['team_size'] ?? null;
        $teamNames = $config['team_names'] ?? null;
        $teamColors = $config['team_colors'] ?? ['#EF4444', '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'];

        // Para modo duel, siempre 2 equipos mínimo
        if ($mode === 'duel' && $teamCount < 2) {
            $teamCount = 2;
        }

        // Nombres por defecto según el modo
        $defaultNames = match ($mode) {
            'duel' => ['Equipo Rojo', 'Equipo Azul'],
            default => array_map(fn ($i) => "Equipo {$i}", range(1, $teamCount)),
        };

        for ($i = 1; $i <= $teamCount; $i++) {
            $name = $teamNames[$i - 1] ?? $defaultNames[$i - 1] ?? "Equipo {$i}";
            $color = $teamColors[$i - 1] ?? $teamColors[array_rand($teamColors)];

            // Para modo duel con 1vsX, el equipo 1 es solo 1 jugador
            $maxPlayers = ($mode === 'duel' && $i === 1 && ($config['duel_type'] ?? null) === '1vsX')
                ? 1
                : ($teamSize ?? (int) ceil($room->max_players / $teamCount));

            TriviaTeam::create([
                'room_id' => $room->id,
                'name' => $name,
                'color' => $color,
                'icon' => $config['team_icons'][$i - 1] ?? null,
                'max_players' => $maxPlayers,
                'current_players' => 0,
                'team_number' => $i,
                'metadata' => [
                    'created_for_mode' => $mode,
                    'duel_type' => $config['duel_type'] ?? null,
                ],
            ]);
        }
    }

    /**
     * Eliminar jugador de Redis (best-effort).
     */
    private function deletePlayerFromRedis(string $playerId, string $roomId) : void
    {
        try {
            Redis::del(self::KEY_PREFIX . "player:{$roomId}:{$playerId}");
        } catch (Throwable) {
            // Redis no disponible — ignorar
        }
    }

    /**
     * Generar código único de sala.
     */
    private function generateRoomCode() : string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (TriviaRoom::where('code', $code)->exists());

        return $code;
    }

    /**
     * Guardar jugador en Redis (best-effort).
     */
    private function savePlayerToRedis(PlayerDTO $player) : void
    {
        try {
            $key = self::KEY_PREFIX . "player:{$player->roomId}:{$player->playerId}";
            Redis::hset($key, 'data', json_encode($player->toArray()));
            Redis::expire($key, self::TTL);
        } catch (Throwable) {
            // Redis no disponible — ignorar
        }
    }

    /**
     * Guardar sala en Redis (best-effort).
     */
    private function saveRoomToRedis(RoomDTO $roomDto) : void
    {
        try {
            $key = self::KEY_PREFIX . $roomDto->roomId;
            Redis::hset($key, 'data', json_encode($roomDto->toArray()));
            Redis::expire($key, self::TTL);
        } catch (Throwable) {
            // Redis no disponible — ignorar
        }
    }
}
