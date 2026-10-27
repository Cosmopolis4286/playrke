<?php

namespace App\Observers\Games\Common;

use App\Models\Games\Common\Utils\GameStatus;
use BadMethodCallException;
use Illuminate\Support\Facades\Cache;

/**
 * Invalida las entradas de Cache generadas por GameStatus::codesMap() cada
 * vez que un estado es creado, actualizado o eliminado.
 *
 * codesMap() cachea bajo el tag 'game_status'. Cuando el driver soporta tags
 * (Redis/Memcached) se hace un flush del tag completo. En drivers sin soporte
 * de tags (file/database/array) la invalidación ocurre por TTL (60 s).
 */
class GameStatusObserver
{
    public function created(GameStatus $_status) : void
    {
        $this->flushCodesMapCache();
    }

    public function deleted(GameStatus $_status) : void
    {
        $this->flushCodesMapCache();
    }

    public function updated(GameStatus $_status) : void
    {
        $this->flushCodesMapCache();
    }

    private function flushCodesMapCache() : void
    {
        try {
            Cache::tags(['game_status'])->flush();
        } catch (BadMethodCallException) {
            // Driver sin soporte de tags — la invalidación ocurre por TTL (60 s).
        }
    }
}
