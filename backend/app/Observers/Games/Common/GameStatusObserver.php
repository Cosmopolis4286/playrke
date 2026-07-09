<?php

namespace App\Observers\Games\Common;

use App\Models\Games\Common\Utils\GameStatus;
use Illuminate\Support\Facades\Cache;

/**
 * Invalida las entradas de Cache generadas por GameStatus::codesMap() cada
 * vez que un estado es creado, actualizado o eliminado.
 *
 * codesMap() genera claves con el patrón "game_status_map_{md5}".
 * Como el sufijo md5 varía según los códigos pedidos, usamos
 * Cache::tags() si el driver lo soporta (Redis/Memcached). Para
 * drivers que no soportan tags (file/database), se almacena la lista
 * de claves activas en una clave centinela y se borran explícitamente.
 */
class GameStatusObserver
{
    public function created(GameStatus $_status): void
    {
        $this->flushCodesMapCache();
    }

    public function updated(GameStatus $_status): void
    {
        $this->flushCodesMapCache();
    }

    public function deleted(GameStatus $_status): void
    {
        $this->flushCodesMapCache();
    }

    private function flushCodesMapCache(): void
    {
        // Elimina las claves registradas por codesMap() sin tocar el resto del cache.
        // GameStatus::codesMap() registra cada clave que genera en la lista centinela
        // 'game_status_map_keys'. El Observer lee esa lista y borra cada entrada.
        $keys = Cache::get('game_status_map_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('game_status_map_keys');
    }
}
