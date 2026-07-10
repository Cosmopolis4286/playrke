<?php

namespace App\Http\Controllers\Games\Bingo\Pattern;

use App\Domains\Games\Bingo\DTOs\Pattern\BingoPatternCreateDTO;
use App\Domains\Games\Bingo\DTOs\Pattern\BingoPatternUpdateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Pattern\BingoPatternRequest;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Domains\Games\Bingo\Services\Pattern\BingoPatternService;
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
    ) {}

    /**
     * Renderiza la página de configuración del bingo.
     */
    public function index(): Response
    {
        return $this->handleServiceInertia(function () {
            $userId   = $this->userContext->resolveUserId();
            $response = $this->service->context('index', $userId);

            return Inertia::render('Games/Bingo/Pattern/Index', $response);
        });
    }

    /**
     * Crear un patron de bingo.
     *
     * @param BingoPatternRequest $request
     * @return JsonResponse
     */
    public function store(BingoPatternRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->create(
                $request->toDTO(BingoPatternCreateDTO::class)
            )
        );
    }

    /**
     * Obtener lista de los patrondes de bingo filtradas por el contexto del usuario (admin o commerce).
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
     * Mostrar detalle de un patron de bingo.
     *
     * @param BingoPattern $pattern
     * @return JsonResponse
     */
    public function show(BingoPattern $pattern): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->show($pattern),
            ]
        );
    }

    /**
     * Actualizar un patron de bingo.
     *
     * @param BingoPatternRequest $request
     * @param BingoPattern $pattern
     * @return JsonResponse
     */
    public function update(BingoPatternRequest $request, BingoPattern $pattern): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->update(
                $pattern,
                $request->toDTO(BingoPatternUpdateDTO::class)
            )
        );
    }

    /**
     * Eliminar un patron bingo (soft delete).
     *
     * @param BingoPattern $pattern
     * @return JsonResponse
     */
    public function delete(BingoPattern $pattern): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($pattern)
        );
    }

    /**
     * Eliminar un patron de bingo permanentemente (hard delete).
     *
     * @param BingoPattern $pattern
     * @return JsonResponse
     */
    public function destroy(BingoPattern $pattern): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($pattern)
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de un patron de bingo.
     *
     * @param BingoPattern $pattern
     * @return JsonResponse
     */
    public function toggleStatus(BingoPattern $pattern): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($pattern)
        );
    }

    /**
     * Alterna el estado de múltiples patrones en una sola operación bulk.
     * Body: { "ids": ["id1", "id2", ...] }
     */
    public function toggleStatusBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['required', 'string', 'exists:bingo_patterns,id'],
        ]);

        return $this->handleServiceJson(
            fn() => ['updated' => $this->service->toggleStatusBulk($validated['ids'])]
        );
    }

    /**
     * Eliminación lógica bulk de patrones.
     * Body: { "ids": ["id1", "id2", ...] }
     */
    public function deleteBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['required', 'string', 'exists:bingo_patterns,id'],
        ]);

        return $this->handleServiceJson(
            fn() => ['deleted' => $this->service->deleteBulk($validated['ids'])]
        );
    }
}
