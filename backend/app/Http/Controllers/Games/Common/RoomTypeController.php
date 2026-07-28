<?php

namespace App\Http\Controllers\Games\Common;

use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Common\RoomTypeRequest;
use App\Services\Games\Common\Room\RoomTypeService;
use Illuminate\Http\JsonResponse;

/**
 * Administración del catálogo global de tipos de sala (RoomType: Privada,
 * Pública, VIP, DEMO). No está atado a un juego específico — protegido por
 * role:superadmin en la ruta, mismo patrón que GameModeController.
 */
final class RoomTypeController extends Controller
{
    public function __construct(
        private readonly RoomTypeService $service,
    ) {}

    public function fetchList(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->list(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->find($id),
        ]);
    }

    public function store(RoomTypeRequest $request): JsonResponse
    {
        try {
            $roomType = $this->service->create($request->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tipo de sala creado exitosamente',
            'data' => $roomType,
        ]);
    }

    public function update(RoomTypeRequest $request, string $id): JsonResponse
    {
        try {
            $roomType = $this->service->update($id, $request->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tipo de sala actualizado exitosamente',
            'data' => $roomType,
        ]);
    }

    public function toggleStatus(string $id): JsonResponse
    {
        $roomType = $this->service->toggleStatus($id);

        return response()->json([
            'success' => true,
            'message' => 'Estado del tipo de sala actualizado exitosamente',
            'data' => $roomType,
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
            'message' => 'Tipo de sala eliminado exitosamente',
        ]);
    }
}
