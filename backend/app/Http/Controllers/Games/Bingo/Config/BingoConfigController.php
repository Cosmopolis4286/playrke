<?php

namespace App\Http\Controllers\Games\Bingo\Config;

use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Config\BingoConfigRequest;
use App\Services\Games\Bingo\Config\BingoConfigService;
use App\Services\Security\UserContextService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de configuración general del juego de Bingo.
 *
 * Responsabilidades:
 * - Mostrar la página de configuración (reglamento e imagen)
 * - Guardar el reglamento e imagen del bingo
 */
final class BingoConfigController extends Controller
{
    public function __construct(
        private readonly BingoConfigService $service,
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

            return Inertia::render('Games/Bingo/Config/Index', $response);
        });
    }

    /**
     * Guarda o actualiza las reglas del bingo (imagen + texto).
     */
    public function save(BingoConfigRequest $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {
            $userId = $this->userContext->resolveUserId();

            $this->service->saveRules(
                userId: $userId,
                rules:  $request->input('rules'),
                image:  $request->file('image'),
            );

            return ['message' => 'Configuración guardada exitosamente.'];
        });
    }
}
