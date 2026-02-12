<?php

namespace App\Http\Controllers\Games\Bingo\Room;

use App\DTOs\Games\Bingo\Room\BingoRoomCreateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Room\BingoRoomRequest;
use App\Models\Games\Bingo\Room\BingoRoom;
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
final class BingoRoomController extends Controller
{
    /**
     * @param BingoRoomService $service
     */
    public function __construct(
        private readonly \App\Services\Games\Bingo\Room\BingoRoomService $service,
        private readonly \App\Services\Security\UserContextService $userContext
    ) {
        $this->authorizeResource(BingoRoom::class, 'id');
    }

    /**
     * Resumen de salas de bingo (admin y commerce).
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $userId = $this->userContext->resolveUserId();
                $response = $this->service->context('summary', $userId);

                return Inertia::render('Games/Bingo/Room/Summary', $response->toView());
            },
            'No se pudo cargar la lista de salas de bingo.'
        );
    }

    /**
     * Crear una sala de bingo.
     *
     * @param BingoRoomRequest $request
     * @return JsonResponse
     */
    public function store(BingoRoomRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->create(
                $request->toDTO(BingoRoomCreateDTO::class)
            )
        );
    }

    /**
     * Obtener lista de salas de bingo filtradas por el contexto del usuario (admin o commerce).
     *
     * @return JsonResponse
     */
    public function fetchList(): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->fetchList()]
        );
    }

    /**
     * Mostrar detalle de una sala de bingo.
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function show(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->show($room),
            ]
        );
    }

    // /**
    //  * Actualizar un comercio.
    //  *
    //  * @param BingoRoomRequest $request
    //  * @param Commerce $commerce
    //  * @return JsonResponse
    //  */
    // public function update(BingoRoomRequest $request, Commerce $commerce): JsonResponse
    // {
    //     return $this->handleServiceJson(
    //         fn() => $this->service->update(
    //             $commerce,
    //             $request->toDTO(AdminCommerceDTO::class)
    //         )
    //     );
    // }

    // /**
    //  * Eliminar un comercio (soft delete).
    //  *
    //  * @param Commerce $commerce
    //  * @return JsonResponse
    //  */
    // public function delete(Commerce $commerce): JsonResponse
    // {
    //     return $this->handleServiceJson(
    //         fn() => $this->service->delete($commerce)
    //     );
    // }

    // /**
    //  * Eliminar un comercio permanentemente (hard delete).
    //  *
    //  * @param Commerce $commerce
    //  * @return JsonResponse
    //  */
    // public function destroy(Commerce $commerce): JsonResponse
    // {
    //     return $this->handleServiceJson(
    //         fn() => $this->service->destroy($commerce)
    //     );
    // }

    // /**
    //  * Alternar estado (ACTIVE / INACTIVE) del comercio.
    //  *
    //  * @param Commerce $commerce
    //  * @return JsonResponse
    //  */
    // public function toggleStatus(Commerce $commerce): JsonResponse
    // {
    //     return $this->handleServiceJson(
    //         fn() => $this->service->toggleStatus($commerce)
    //     );
    // }

    // /**
    //  * Generar una nueva contraseña para un comercio.
    //  *
    //  * @param Commerce $commerce
    //  * @return JsonResponse
    //  */
    // public function resetPassword(Commerce $commerce): JsonResponse
    // {
    //     return $this->handleServiceJson(
    //         fn() => [
    //             'password' => $this->service->resetPassword($commerce),
    //         ]
    //     );
    // }
}
