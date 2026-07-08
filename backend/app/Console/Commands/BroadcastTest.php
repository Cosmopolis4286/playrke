<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domains\Games\Bingo\Events\BingoRoomUpdated;

class BroadcastTest extends Command
{
    /**
     * Nombre y firma del comando.
     * Permite pasar un roomId opcional.
     */
    protected $signature = 'app:broadcast-test
                            {roomId? : ID de la sala de bingo (si no se provee se genera uno aleatorio)}
                            {--prize=150.75 : Valor del prize pool}
                            {--players=8 : Cantidad de jugadores}
                            {--price=2.50 : Precio del cartón en USD}';

    /**
     * Descripción del comando.
     */
    protected $description = 'Envía un evento de prueba a un canal de bingo para verificar broadcasting';

    /**
     * Ejecuta el comando.
     */
    public function handle()
    {
        $roomId = $this->argument('roomId') ?? 'test-room-' . time();
        $prizePool = (float) $this->option('prize');
        $totalPlayers = (int) $this->option('players');
        $cardPriceUsd = (float) $this->option('price');

        $event = new BingoRoomUpdated(
            roomId: $roomId,
            prizePool: $prizePool,
            totalPlayers: $totalPlayers,
            cardPriceUsd: $cardPriceUsd
        );

        broadcast($event);

        $this->info("✅ Evento enviado a bingo.room.$roomId");
        $this->info("   prizePool: $prizePool, totalPlayers: $totalPlayers, cardPriceUsd: $cardPriceUsd");
        $this->info("📱 Revisa la consola del navegador para confirmar recepción del evento");
    }
}