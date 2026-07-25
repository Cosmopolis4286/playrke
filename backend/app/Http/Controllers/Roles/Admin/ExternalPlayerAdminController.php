<?php

namespace App\Http\Controllers\Roles\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Admin\ExternalPlayer\ExternalPlayerSearchRequest;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Services\Roles\Admin\ExternalPlayerAdminService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel admin de Jugadores Externos (compradores de cartones de Bingo sin
 * cuenta en la plataforma — ver App\Traits\Users\PlayerLinkTrait). Protegido
 * por middleware `role:admin,superadmin` en la ruta y por ExternalPlayerPolicy.
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

    public function toggleBlock(string $externalPlayer): JsonResponse
    {
        $player = ExternalPlayer::findOrFail($externalPlayer);
        $this->authorize('update', $player);

        $updated = $this->service->toggleBlock($externalPlayer);

        return response()->json([
            'message' => 'Jugador actualizado correctamente',
            'player'  => $updated,
        ]);
    }
}
