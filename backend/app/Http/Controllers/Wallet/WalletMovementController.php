<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Wallet\WalletMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración de Movimientos de Billetera.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP para gestión de movimientos
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class WalletMovementController extends Controller
{
    /**
     * Constructor del controlador.
     */
    public function __construct(
        //private readonly WalletMovementService $service
    ) {
        $this->authorizeResource(WalletMovement::class, 'movement');
    }

    /**
     * Vista principal de movimientos de billetera.
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->handleServiceInertia(
            function () {
                // $response = $this->service->context();
                // $response['title'] = 'Movimientos de Billetera';

                return Inertia::render('Wallet/Movements/Index', [
                    'title' => 'Movimientos de Billetera',
                    'movements' => [], // Placeholder para datos
                ]);
            },
            'No se pudo cargar la lista de movimientos.'
        );
    }

    /**
     * Obtener lista paginada de movimientos.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchList(Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => [], // Placeholder
            ]
        );
    }

    /**
     * Ver detalle de un movimiento específico.
     *
     * @param WalletMovement $movement
     * @return JsonResponse
     */
    public function show(WalletMovement $movement): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $movement,
            ]
        );
    }
}
