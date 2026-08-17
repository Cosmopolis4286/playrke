<?php

namespace App\Http\Controllers\Games\Common;

use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Common\JackpotTypeRequest;
use App\Services\Games\Common\Jackpot\JackpotTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Administración del catálogo de tipos de jackpot (JackpotType), específico
 * por juego (Bingo, Trivia, etc.) — protegido por role:superadmin,admin en
 * la ruta. Consumido por la pantalla con tabs de games.index.
 */
final class JackpotTypeController extends Controller
{
    public function __construct(
        private readonly JackpotTypeService $service,
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

    public function store(JackpotTypeRequest $request): JsonResponse
    {
        try {
            $jackpotType = $this->service->create($request->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jackpot creado exitosamente',
            'data' => $jackpotType,
        ]);
    }

    public function update(JackpotTypeRequest $request, string $id): JsonResponse
    {
        try {
            $jackpotType = $this->service->update($id, $request->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jackpot actualizado exitosamente',
            'data' => $jackpotType,
        ]);
    }

    public function toggleStatus(string $id): JsonResponse
    {
        $jackpotType = $this->service->toggleStatus($id);

        return response()->json([
            'success' => true,
            'message' => 'Estado del jackpot actualizado exitosamente',
            'data' => $jackpotType,
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
            'message' => 'Jackpot eliminado exitosamente',
        ]);
    }
}
