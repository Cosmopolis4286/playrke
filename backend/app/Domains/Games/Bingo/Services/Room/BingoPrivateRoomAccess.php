<?php

namespace App\Domains\Games\Bingo\Services\Room;

use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Reglas de las salas PRIVATE (código + contraseña):
 *
 * - El código de acceso es único entre las salas privadas que todavía se
 *   pueden jugar (no terminadas, canceladas ni borradas): "Tengo un código"
 *   nunca debe poder llevar a un jugador a la sala equivocada.
 * - Una sala privada no se activa sin código y contraseña (antes una copia
 *   o una sala generada quedaba activa pero inaccesible para los jugadores).
 * - Quien ya compró cartones en la sala cuenta como verificado aunque cambie
 *   de dispositivo o de sesión.
 * - Límite de intentos fallidos por jugador y por sala (fuerza bruta con
 *   varias cuentas), con aviso al creador cuando se bloquea su sala.
 */
class BingoPrivateRoomAccess
{
    /** Estados en los que la sala ya no reserva su código. */
    public const CLOSED_STATUSES = ['FINISHED', 'CANCELLED', 'DELETED'];

    /** Sin 0/O ni 1/I/L: códigos fáciles de dictar y copiar. */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const LOCK_SECONDS = 900;

    private const ROOM_MAX_FAILURES = 30;

    private const USER_MAX_FAILURES = 5;

    public static function isPrivate(?BingoRoomConfig $config) : bool
    {
        return $config?->roomType?->code === 'PRIVATE';
    }

    private static function roomKey(string $roomId) : string
    {
        return 'bingo-private-fail:room:' . $roomId;
    }

    private static function userKey(string $userId) : string
    {
        return 'bingo-private-fail:user:' . $userId;
    }

    public function assertHasCredentials(BingoRoom $room) : void
    {
        $room->loadMissing('config.roomType');
        $config = $room->config;

        if (self::isPrivate($config) && (blank($config->access_code) || blank($config->room_password))) {
            throw BingoRoomException::privateRoomMissingCredentials();
        }
    }

    public function assertNotLocked(?string $userId, ?BingoRoom $room = null) : void
    {
        if ($userId !== null && RateLimiter::tooManyAttempts(self::userKey($userId), self::USER_MAX_FAILURES)) {
            throw BingoRoomException::privateAccessLocked();
        }
        if ($room !== null && RateLimiter::tooManyAttempts(self::roomKey((string) $room->id), self::ROOM_MAX_FAILURES)) {
            throw BingoRoomException::privateAccessLocked();
        }
    }

    public function clearFailures(?string $userId) : void
    {
        if ($userId !== null) {
            RateLimiter::clear(self::userKey($userId));
        }
    }

    /**
     * Código para una sala generada desde una plantilla: el de la plantilla si
     * está libre; si no (otra sala de la misma plantilla sigue abierta, o se
     * generan varias por moneda), el de la plantilla con un sufijo único.
     */
    public function codeForGeneratedRoom(?string $templateCode, ?string $suffixHint = null) : string
    {
        $base = strtoupper(trim((string) $templateCode));
        if ($base === '') {
            return $this->generateUniqueCode();
        }
        if (! $this->isAccessCodeTaken($base)) {
            return $base;
        }

        foreach (array_filter([$suffixHint ? strtoupper($suffixHint) : null]) as $hint) {
            $candidate = mb_substr($base, 0, 20 - strlen($hint) - 1) . '-' . $hint;
            if (! $this->isAccessCodeTaken($candidate)) {
                return $candidate;
            }
        }

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $suffix = '';
            for ($i = 0; $i < 4; $i++) {
                $suffix .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
            $candidate = mb_substr($base, 0, 15) . '-' . $suffix;
            if (! $this->isAccessCodeTaken($candidate)) {
                return $candidate;
            }
        }

        throw BingoRoomException::accessCodeUnavailable();
    }

    /** Código legible y libre, ej. "K7M4PX9Q" (8 caracteres de un alfabeto sin ambigüedades). */
    public function generateUniqueCode(int $length = 8) : string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
            if (! $this->isAccessCodeTaken($code)) {
                return $code;
            }
        }

        throw BingoRoomException::accessCodeUnavailable();
    }

    /** El jugador ya compró cartones: puede volver sin reingresar el código. */
    public function hasPurchasedCards(BingoRoom $room, ?string $userId) : bool
    {
        if ($userId === null) {
            return false;
        }

        return BingoRoomUser::where('room_id', (string) $room->id)
            ->where('user_id', $userId)
            ->where('cards_purchased', '>', 0)
            ->exists();
    }

    /** ¿Otra sala privada abierta ya usa este código? (sin distinguir mayúsculas) */
    public function isAccessCodeTaken(string $accessCode, ?string $exceptRoomId = null) : bool
    {
        return BingoRoomConfig::query()
            ->whereRaw('LOWER(access_code) = ?', [mb_strtolower(trim($accessCode))])
            ->whereHas('roomType', fn ($q) => $q->where('code', 'PRIVATE'))
            ->whereHas('room', fn ($q) => $q
                ->when($exceptRoomId, fn ($q) => $q->where('id', '!=', $exceptRoomId))
                ->whereHas('status', fn ($s) => $s->whereNotIn('code', self::CLOSED_STATUSES)))
            ->exists();
    }

    /** @param iterable<BingoRoom> $rooms salas cuyo código coincidió pero la contraseña no */
    public function recordFailure(?string $userId, iterable $rooms = []) : void
    {
        if ($userId !== null) {
            RateLimiter::hit(self::userKey($userId), self::LOCK_SECONDS);
        }

        foreach ($rooms as $room) {
            $key = self::roomKey((string) $room->id);
            RateLimiter::hit($key, self::LOCK_SECONDS);

            if (RateLimiter::attempts($key) === self::ROOM_MAX_FAILURES) {
                $this->notifyCreatorOfLock($room);
            }
        }
    }

    private function notifyCreatorOfLock(BingoRoom $room) : void
    {
        // Un aviso por ventana de bloqueo.
        if (! Cache::add('bingo:private-lock-notified:' . $room->id, true, self::LOCK_SECONDS)) {
            return;
        }

        Log::warning('[BingoPrivateRoomAccess] Sala privada bloqueada por intentos fallidos', ['room_id' => (string) $room->id]);

        try {
            app(NotificationService::class)->create(
                (string) $room->creator_user_id,
                'GAME_ALERT',
                'Intentos de acceso a tu sala privada',
                'Hubo muchos intentos fallidos de entrar a tu sala privada #' . ($room->uuid_code ?? $room->id)
                    . '. Bloqueamos el acceso por 15 minutos. Si no compartiste la contraseña, cámbiala.',
            );
        } catch (Throwable $e) {
            Log::warning('[BingoPrivateRoomAccess] No se pudo notificar al creador', ['room_id' => (string) $room->id, 'error' => $e->getMessage()]);
        }
    }
}
