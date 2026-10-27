<?php

namespace App\Http\Controllers\Games\Bingo\Pattern;

use App\Domains\Games\Bingo\DTOs\Pattern\BingoPatternCreateDTO;
use App\Domains\Games\Bingo\DTOs\Pattern\BingoPatternUpdateDTO;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Domains\Games\Bingo\Services\Pattern\BingoPatternService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Pattern\BingoPatternRequest;
use App\Services\Security\UserContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de configuración de patrones (cantos/jugadas) del juego de Bingo.
 *
 * Responsabilidades:
 * - Renderizar la página de patrones
 * -
 * -
 */
final class BingoPatternController extends Controller
{
    public function __construct(
        private readonly BingoPatternService $service,
        private readonly UserContextService $userContext,
    ) {
    }

    /**
     * Eliminar un patron bingo (soft delete).
     */
    public function delete(BingoPattern $pattern) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->delete($pattern),
        );
    }

    /**
     * Eliminación lógica bulk de patrones.
     * Body: { "ids": ["id1", "id2", ...] }
     */
    public function deleteBulk(Request $request) : JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['required', 'string', 'exists:bingo_patterns,id'],
        ]);

        return $this->handleServiceJson(
            fn () => ['deleted' => $this->service->deleteBulk($validated['ids'])],
        );
    }

    /**
     * Eliminar un patron de bingo permanentemente (hard delete).
     */
    public function destroy(BingoPattern $pattern) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->destroy($pattern),
        );
    }

    /**
     * Obtener lista de los patrondes de bingo filtradas por el contexto del usuario (admin o commerce).
     */
    public function fetchList() : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => ['data' => $this->service->fetchList()],
        );
    }

    /**
     * Renderiza la página de configuración del bingo.
     */
    public function index() : Response
    {
        return $this->handleServiceInertia(function() {
            $userId = $this->userContext->resolveUserId();
            $response = $this->service->context('index', $userId);

            return Inertia::render('Games/Bingo/Pattern/Index', $response);
        });
    }

    /**
     * Mostrar detalle de un patron de bingo.
     */
    public function show(BingoPattern $pattern) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'data' => $this->service->show($pattern),
            ],
        );
    }

    /**
     * Crear un patron de bingo.
     */
    public function store(BingoPatternRequest $request) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->create(
                $request->toDTO(BingoPatternCreateDTO::class),
            ),
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de un patron de bingo.
     */
    public function toggleStatus(BingoPattern $pattern) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->toggleStatus($pattern),
        );
    }

    /**
     * Alterna el estado de múltiples patrones en una sola operación bulk.
     * Body: { "ids": ["id1", "id2", ...] }
     */
    public function toggleStatusBulk(Request $request) : JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['required', 'string', 'exists:bingo_patterns,id'],
        ]);

        return $this->handleServiceJson(
            fn () => ['updated' => $this->service->toggleStatusBulk($validated['ids'])],
        );
    }

    /**
     * Actualizar un patron de bingo.
     */
    public function update(BingoPatternRequest $request, BingoPattern $pattern) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->update(
                $pattern,
                $request->toDTO(BingoPatternUpdateDTO::class),
            ),
        );
    }
}
