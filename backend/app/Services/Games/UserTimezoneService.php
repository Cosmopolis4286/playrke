<?php

namespace App\Services\Games;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Resuelve el timezone IANA (ej. "America/Caracas") de un usuario a partir de
 * su `users.time_zone_id`. Único punto de esta lógica en la plataforma —
 * cualquier dominio de juego (Bingo, y los que se agreguen después) que
 * necesite comparar fechas "de hoy" contra columnas guardadas en el timezone
 * de un usuario (creador, jugador, etc.) debe pasar por aquí en vez de
 * repetir el join a `time_zones` en cada servicio.
 *
 * Sin esto, comparar `now()` (timezone del servidor, UTC) contra columnas de
 * fecha guardadas en el timezone del usuario produce ventanas desalineadas:
 * una sala programada "mañana a las 23:00" en horario de Caracas (UTC-4)
 * puede caer fuera de una ventana calculada con el "hoy" de UTC.
 */
class UserTimezoneService
{
    private const CACHE_TTL_SECONDS = 300;

    /**
     * @param int $userId
     * @return string Nombre de timezone IANA, o 'UTC' si el usuario no existe
     *                 o no tiene timezone configurado (nunca falla la carga
     *                 de la página por un dato de perfil faltante).
     */
    public function resolve(int $userId): string
    {
        return Cache::remember(
            "user-timezone:{$userId}",
            self::CACHE_TTL_SECONDS,
            function () use ($userId): string {
                $tzName = User::where('users.id', $userId)
                    ->join('time_zones', 'users.time_zone_id', '=', 'time_zones.id')
                    ->value('time_zones.name');

                return $tzName ?? 'UTC';
            }
        );
    }
}
