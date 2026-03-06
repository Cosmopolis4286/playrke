<?php

namespace App\Http\Controllers\Roles\Admin;

use App\DTOs\Roles\Admin\Player\AdminPlayerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Admin\Player\AdminPlayerRequest;
use App\Models\User;
use App\Services\Roles\Admin\Player\AdminPlayerService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de administración de Players.
 *
 * Player = User con rol 'player' (sin tabla propia).
 */
final class AdminPlayerController extends Controller
{
    public function __construct(
        protected readonly AdminPlayerService $service
    ) {}

    public function store(AdminPlayerRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->create(
                    $request->toDTO(AdminPlayerDTO::class)
                ),
            ]
        );
    }

    public function fetchList(): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->fetchList()]
        );
    }

    public function show(User $player): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->show($player)]
        );
    }

    public function update(AdminPlayerRequest $request, User $player): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->update(
                $player,
                $request->toDTO(AdminPlayerDTO::class)
            )
        );
    }

    public function delete(User $player): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($player)
        );
    }

    public function destroy(User $player): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($player)
        );
    }

    public function toggleStatus(User $player): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($player)
        );
    }

    public function resetPassword(User $player): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->resetPassword($player),
            ]
        );
    }
}
