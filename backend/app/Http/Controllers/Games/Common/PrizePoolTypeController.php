<?php

namespace App\Http\Controllers\Games\Common;

use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Common\PrizePoolTypeRequest;
use App\Services\Games\Common\PrizePool\PrizePoolTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Administración del catálogo de tipos de bote (PrizePoolType), específico
 * por juego (Bingo, Trivia, etc.) — protegido por role:superadmin,admin en
 * la ruta. Consumido por la pantalla con tabs de games.index.
 */
final class PrizePoolTypeController extends Controller
{
    public function __construct(
        private readonly PrizePoolTypeService $service,
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

    public function store(PrizePoolTypeRequest $request): JsonResponse
    {
        try {
            $prizePoolType = $this->service->create($request->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tipo de bote creado exitosamente',
            'data' => $prizePoolType,
        ]);
    }

    public function update(PrizePoolTypeRequest $request, string $id): JsonResponse
    {
        try {
            $prizePoolType = $this->service->update($id, $request->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tipo de bote actualizado exitosamente',
            'data' => $prizePoolType,
        ]);
    }

    public function toggleStatus(string $id): JsonResponse
    {
        $prizePoolType = $this->service->toggleStatus($id);

        return response()->json([
            'success' => true,
            'message' => 'Estado del tipo de bote actualizado exitosamente',
            'data' => $prizePoolType,
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
            'message' => 'Tipo de bote eliminado exitosamente',
        ]);
    }
}
