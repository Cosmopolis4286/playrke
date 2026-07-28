<?php

namespace App\Http\Controllers\Games\Common;

use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Common\GameModeRequest;
use App\Services\Games\Common\GameMode\GameModeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Administración del catálogo de modos de juego (GameMode), específico por
 * juego (Bingo, Trivia, etc.) — protegido por role:superadmin en la ruta.
 * Consumido por la pantalla con tabs de games.index (GameController@index).
 */
final class GameModeController extends Controller
{
    public function __construct(
        private readonly GameModeService $service,
    ) {}

    public function fetchList(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->listByGame($request->get('game_id')),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->find($id),
        ]);
    }

    public function store(GameModeRequest $request): JsonResponse
    {
        try {
            $gameMode = $this->service->create($request->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Modo de juego creado exitosamente',
            'data' => $gameMode,
        ]);
    }

    public function update(GameModeRequest $request, string $id): JsonResponse
    {
        try {
            $gameMode = $this->service->update($id, $request->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Modo de juego actualizado exitosamente',
            'data' => $gameMode,
        ]);
    }

    public function toggleStatus(string $id): JsonResponse
    {
        $gameMode = $this->service->toggleStatus($id);

        return response()->json([
            'success' => true,
            'message' => 'Estado del modo de juego actualizado exitosamente',
            'data' => $gameMode,
        ]);
    }

    public function delete(string $id): JsonResponse
    {
        try {
            $this->service->delete($id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Modo de juego eliminado exitosamente',
        ]);
    }
}
