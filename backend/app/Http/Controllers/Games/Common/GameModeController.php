<?php

namespace App\Http\Controllers\Games\Common;

use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Common\GameModeRequest;
use App\Services\Games\Common\GameMode\GameModeService;
use Illuminate\Http\JsonResponse;

/**
 * Administración del catálogo de modos de juego (GameMode), específico por
 * juego (Bingo, Trivia, etc.) — protegido por multiPermission:games.manage
 * en la ruta, mismo patrón que el resto de la administración de Bingo.
 */
final class GameModeController extends Controller
{
    public function __construct(
        private readonly GameModeService $service,
    ) {}

    public function fetchList(string $game): JsonResponse
    {
        return response()->json([
            'gameModes' => $this->service->listByGame($game),
        ]);
    }

    public function store(GameModeRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $gameMode = $this->service->create($data['gameId'], $data['code'], $data['label']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Modo de juego creado correctamente.',
            'gameMode' => $gameMode,
        ], 201);
    }

    public function update(GameModeRequest $request, string $gameMode): JsonResponse
    {
        $data = $request->validated();

        try {
            $updated = $this->service->update($gameMode, $data['code'], $data['label']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Modo de juego actualizado correctamente.',
            'gameMode' => $updated,
        ]);
    }

    public function toggleStatus(string $gameMode): JsonResponse
    {
        $updated = $this->service->toggleStatus($gameMode);

        return response()->json([
            'message' => 'Estado actualizado correctamente.',
            'gameMode' => $updated,
        ]);
    }

    public function destroy(string $gameMode): JsonResponse
    {
        try {
            $this->service->delete($gameMode);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Modo de juego eliminado correctamente.',
        ]);
    }
}
