<?php

namespace App\Http\Controllers\Roles\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Admin\ExternalPlayer\ExternalPlayerSearchRequest;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Services\Roles\Admin\ExternalPlayerAdminService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel admin de Jugadores Externos (compradores sin cuenta en la
 * plataforma, captados por un vendedor — no exclusivo de Bingo, ver
 * App\Traits\Users\PlayerLinkTrait). Protegido por middleware
 * `role:admin,superadmin` en la ruta y por ExternalPlayerPolicy.
 */
final class ExternalPlayerAdminController extends Controller
{
    public function __construct(
        protected readonly ExternalPlayerAdminService $service,
    ) {}

    public function index(ExternalPlayerSearchRequest $request): Response
    {
        $this->authorize('viewAny', ExternalPlayer::class);

        return Inertia::render('Roles/Admin/ExternalPlayer/Index', [
            'players'    => $this->service->search($request->filters(), $request->perPage()),
            'statistics' => $this->service->statistics(),
            'games'      => $this->service->availableGames(),
        ]);
    }

    public function fetchList(ExternalPlayerSearchRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ExternalPlayer::class);

        $filters = $request->filters();

        return response()->json([
            'players'    => $this->service->search($filters, $request->perPage()),
            'statistics' => $this->service->statistics($filters['game_id']),
        ]);
    }

    /**
     * Exporta a CSV todos los jugadores externos que coincidan con los
     * filtros actuales (sin límite de paginación) — usa cursor() vía el
     * service para no materializar el resultado completo en memoria.
     */
    public function export(ExternalPlayerSearchRequest $request): StreamedResponse
    {
        $this->authorize('export', ExternalPlayer::class);

        $filters = $request->filters();

        return response()->streamDownload(function () use ($filters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nombre', 'Apellido', 'Teléfono', 'Identificación', 'Estado', 'Juego', 'País', 'Vendedor', 'Expira', 'Link']);

            foreach ($this->service->searchForExport($filters) as $player) {
                fputcsv($handle, [
                    $player->first_name,
                    $player->last_name,
                    $player->phone,
                    $player->identification,
                    $player->status,
                    $player->game?->name,
                    $player->country?->name,
                    $player->sellerUser ? "{$player->sellerUser->first_name} {$player->sellerUser->last_name}" : null,
                    $player->expiration_date?->format('Y-m-d'),
                    $player->link,
                ]);
            }

            fclose($handle);
        }, 'jugadores-externos-' . now()->format('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function toggleBlock(string $externalPlayer): JsonResponse
    {
        $player = ExternalPlayer::findOrFail($externalPlayer);
        $this->authorize('toggleBlock', $player);

        $updated = $this->service->toggleBlock($externalPlayer);

        return response()->json([
            'message' => 'Jugador actualizado correctamente',
            'player'  => $updated,
        ]);
    }
}
