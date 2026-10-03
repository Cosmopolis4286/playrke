<?php

namespace App\Http\Controllers;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Game\BingoGameService;
use App\Domains\Games\Bingo\Services\Game\BingoWinnerDetectionService;
use App\Models\Roles\Player\External\ExternalPlayer;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vista de juego PÚBLICA (sin sesión de User) para un ExternalPlayer
 * (comprador de cartones sin cuenta, captado por un vendedor — ver
 * App\Services\Roles\Player\External\ExternalPlayerLinkService).
 *
 * Deliberadamente NO usa BingoRoomPolicy ni $this->authorize(): esa policy
 * exige $user->hasRole('player') (ver BingoRoomPolicy::viewGame()), lo que
 * un ExternalPlayer sin cuenta jamás puede satisfacer. La autorización aquí
 * es el `link` mismo — un token de 28 caracteres aleatorios (ver
 * ExternalPlayerRepository::LINK_LENGTH) que actúa como credencial de
 * acceso; equivalente en seguridad a una URL firmada de un solo destinatario.
 *
 * NO exponer aquí ninguna acción que muta datos de otros jugadores ni de la
 * sala — sólo lectura de "mis cartones" + el gesto de reclamar bingo propio.
 */
final class ExternalPlayerGameController extends Controller
{
    public function __construct(
        private readonly BingoGameService $service,
        private readonly BingoWinnerDetectionService $winnerDetection,
    ) {}

    /**
     * Resuelve el ExternalPlayer activo (no expirado) por su link, o 404.
     */
    private function resolvePlayer(string $link): ExternalPlayer
    {
        $player = ExternalPlayer::where('link', $link)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$player) {
            abort(404, 'Link no encontrado o inactivo.');
        }

        if ($player->expiration_date && $player->expiration_date->isPast()) {
            abort(410, 'Este link de acceso ha expirado.');
        }

        return $player;
    }

    /**
     * Vista del juego de bingo para el jugador externo.
     */
    public function game(string $link): Response
    {
        $player = $this->resolvePlayer($link);
        $room   = BingoRoom::findOrFail($player->room_id);

        $response = $this->service->getInGameDataForExternalPlayer($room, $player);
        $response['isLocal']         = app()->environment('local');
        $response['externalPlayer']  = [
            'link'       => $player->link,
            'first_name' => $player->first_name,
            'last_name'  => $player->last_name,
            // Usados solo para precargar el formulario de registro (CTA del
            // banner de invitado) — permite que RegisteredUserController::
            // linkExternalPlayerHistory() encuentre coincidencia exacta al
            // registrarse. No se usan para nada más en esta pantalla.
            'phone'      => $player->phone,
            'country_id' => (string) $player->country_id,
            // Pago en revisión / rechazado / premios y a quién acudir.
            'status'     => $this->service->getExternalPlayerPurchaseStatus($player),
        ];

        return Inertia::render('Games/Bingo/Game/Index', $response);
    }

    /**
     * Estado actual del sorteo (API) — mismo contrato que
     * BingoGameController::drawState(), no depende de identidad del jugador.
     */
    public function drawState(string $link): JsonResponse
    {
        $player = $this->resolvePlayer($link);
        $room   = BingoRoom::findOrFail($player->room_id);

        return response()->json($this->service->getDrawState($room));
    }

    /**
     * Cartones del jugador externo (API).
     */
    public function getUserCards(string $link): JsonResponse
    {
        $player = $this->resolvePlayer($link);
        $room   = BingoRoom::findOrFail($player->room_id);

        $room->loadMissing('status');
        $roomIsFinished = strtoupper($room->status?->code ?? '') === 'FINISHED';

        $cartons = $this->service->getUserCartonsForExternalPlayer((string) $player->id, $roomIsFinished);

        return response()->json([
            'success' => true,
            'data'    => $cartons,
            'message' => 'Cartones obtenidos exitosamente',
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Verifica si el jugador externo tiene bingo en alguno de sus cartones.
     */
    public function checkBingo(string $link): JsonResponse
    {
        $player = $this->resolvePlayer($link);
        $room   = BingoRoom::findOrFail($player->room_id);

        $winner = $this->winnerDetection->checkWinnerForExternalPlayer($room, (string) $player->id);

        if ($winner) {
            return response()->json([
                'success'  => true,
                'hasBingo' => true,
                'winner'   => $winner,
                'message'  => '¡BINGO! Has completado tu cartón.',
            ]);
        }

        return response()->json([
            'success'  => true,
            'hasBingo' => false,
            'message'  => 'No tienes bingo aún. Sigue atento.',
        ]);
    }
}
