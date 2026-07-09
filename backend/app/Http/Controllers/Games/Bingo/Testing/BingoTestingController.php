<?php

namespace App\Http\Controllers\Games\Bingo\Testing;

use App\Domains\Games\Bingo\DTOs\Card\Generate\BingoCardGenerateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Card\BingoCardGenerateRequest;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Card\Generate\BingoCardGenerateService;
use Illuminate\Http\JsonResponse;
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
final class BingoTestingController extends Controller
{
    /**
     * @param BingoRoomService $service
     */
    public function __construct(
        private readonly BingoCardGenerateService $generateService,
    ) {
        $this->authorizeResource(BingoRoom::class, 'room');
    }

    /**
     * Generar cartones de bingo para un jugador (rol seller).
     *
     * @param BingoCardGenerateRequest $request
     * @return JsonResponse
     */
    public function generateCard(BingoCardGenerateRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->generateService->generate(
                $request->toDTO(BingoCardGenerateDTO::class)
            )
        );
    }

}
