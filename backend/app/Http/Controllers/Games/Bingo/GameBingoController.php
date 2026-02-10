<?php

namespace App\Http\Controllers\Games\Bingo;

use App\Http\Controllers\Controller;
use App\Models\Bingo\Rooms\BingoRoom;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración del juego de Bingo.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class GameBingoController extends Controller
{
    /**
     * @param GameBingoService $service
     */
    public function __construct(
        protected readonly \App\Services\Games\Bingo\GameBingoService $service
    ) {
        $this->authorizeResource(BingoRoom::class, 'id');
    }

    /**
     * Resumen de salas de bingo (admin y commerce).
     *
     * @return Response
     */
    public function gameRooms(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $response = $this->service->gameRooms('summary');

                $response['title'] = 'Salas de Bingo';

                return Inertia::render('Games/Bingo/GameRoom/Summary', $response);
            },
            'No se pudo cargar la lista de salas de bingo.'
        );
    }
}
