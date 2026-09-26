<?php

namespace App\Http\Controllers\Roles\Admin;

use App\DTOs\Roles\Admin\Player\AdminPlayerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Admin\Player\AdminPlayerRequest;
use App\Models\User;
use App\Policies\Roles\Admin\AdminPlayerPolicy;
use App\Services\Roles\Admin\Player\AdminPlayerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $this->authorize('create', AdminPlayerPolicy::class);

        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->create(
                    $request->toDTO(AdminPlayerDTO::class)
                ),
            ]
        );
    }

    /**
     * Listado paginado de jugadores (JSON). Server-side real: search,
     * country_name y status filtran contra TODO el catálogo de jugadores,
     * no solo la página cargada en memoria.
     */
    public function fetchList(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AdminPlayerPolicy::class);

        return $this->handleServiceJson(function () use ($request) {
            $filters = $request->only(['search', 'country_name', 'status']);
            $page    = (int) $request->get('page', 1);

            $result = $this->service->fetchList($filters, $page);

            return [
                'data' => $result['rows'],
                'meta' => $result['pagination'],
            ];
        });
    }

    /**
     * Exporta a CSV todos los jugadores que coincidan con los filtros
     * actuales (sin límite de paginación) — mismo patrón que
     * AdminUserController::export().
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', AdminPlayerPolicy::class);

        $filters = $request->only(['search', 'country_name', 'status']);

        return response()->streamDownload(function () use ($filters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nombre', 'Apellido', 'Username', 'Email', 'País', 'Teléfono', 'Estado', 'Registrado']);

            foreach ($this->service->exportForCsv($filters) as $user) {
                fputcsv($handle, [
                    $user->first_name,
                    $user->last_name,
                    $user->username,
                    $user->email,
                    $user->country?->name,
                    $user->phone_number,
                    $user->status,
                    $user->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 'jugadores-' . now()->format('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function show(User $player): JsonResponse
    {
        $this->authorize('view', AdminPlayerPolicy::class);

        return $this->handleServiceJson(
            fn() => ['data' => $this->service->show($player)]
        );
    }

    public function update(AdminPlayerRequest $request, User $player): JsonResponse
    {
        $this->authorize('update', AdminPlayerPolicy::class);

        return $this->handleServiceJson(
            fn() => $this->service->update(
                $player,
                $request->toDTO(AdminPlayerDTO::class)
            )
        );
    }

    public function delete(User $player): JsonResponse
    {
        $this->authorize('delete', AdminPlayerPolicy::class);

        return $this->handleServiceJson(
            fn() => $this->service->delete($player)
        );
    }

    public function destroy(User $player): JsonResponse
    {
        $this->authorize('delete', AdminPlayerPolicy::class);

        return $this->handleServiceJson(
            fn() => $this->service->destroy($player)
        );
    }

    public function toggleStatus(User $player): JsonResponse
    {
        $this->authorize('toggleStatus', AdminPlayerPolicy::class);

        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($player)
        );
    }

    /**
     * Alternar estado de varios jugadores en una sola transacción.
     */
    public function toggleStatusBulk(Request $request): JsonResponse
    {
        $this->authorize('toggleStatus', AdminPlayerPolicy::class);

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        return $this->handleServiceJson(
            fn() => $this->service->toggleStatusBulk($request->input('ids'))
        );
    }

    /**
     * Eliminación lógica de varios jugadores en una sola transacción.
     */
    public function deleteBulk(Request $request): JsonResponse
    {
        $this->authorize('delete', AdminPlayerPolicy::class);

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        return $this->handleServiceJson(
            fn() => $this->service->deleteBulk($request->input('ids'))
        );
    }

    public function resetPassword(User $player): JsonResponse
    {
        $this->authorize('resetPassword', AdminPlayerPolicy::class);

        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->resetPassword($player),
            ]
        );
    }
}
