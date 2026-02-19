<?php

namespace App\Http\Controllers\Games\Bingo\Config;

use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Config\BingoConfigRequest;
use App\Http\Requests\Games\Bingo\Config\BingoConfigImageRequest;
use App\Services\Games\Bingo\Config\BingoConfigService;
use App\Services\Security\UserContextService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de configuración general del juego de Bingo.
 *
 * Responsabilidades:
 * - Renderizar la página de configuración
 * - Guardar el reglamento (texto) de forma independiente
 * - Guardar la imagen de forma independiente
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
     * Guarda solo el texto del reglamento.
     */
    public function saveRules(BingoConfigRequest $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {
            $this->service->saveRules(
                userId: $this->userContext->resolveUserId(),
                rules:  $request->input('rules'),
            );

            return ['message' => 'Reglamento guardado exitosamente.'];
        });
    }

    /**
     * Guarda solo la imagen del reglamento.
     */
    public function saveImage(BingoConfigImageRequest $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {
            $this->service->saveImage(
                userId: $this->userContext->resolveUserId(),
                image:  $request->file('image'),
            );

            return ['message' => 'Imagen guardada exitosamente.'];
        });
    }
}
