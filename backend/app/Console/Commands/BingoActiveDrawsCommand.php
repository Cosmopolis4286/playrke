<?php

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use Illuminate\Console\Command;

/**
 * Cuántos sorteos de Bingo están en curso (salas IN_PROGRESS). Lo usa
 * deploy.sh para no reiniciar los workers del sorteo en plena partida: el
 * sorteo se recupera solo (heartbeat), pero los jugadores verían una pausa.
 * Imprime solo el número, para leerlo desde un script.
 */
class BingoActiveDrawsCommand extends Command
{
    protected $description = 'Cantidad de sorteos de Bingo en curso (salas IN_PROGRESS)';

    protected $signature = 'bingo:active-draws';

    public function handle() : int
    {
        $count = BingoRoom::whereHas('status', fn ($q) => $q->where('code', 'IN_PROGRESS'))->count();

        $this->line((string) $count);

        return self::SUCCESS;
    }
}
