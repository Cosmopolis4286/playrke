<?php

namespace App\Http\Controllers\Roles\Admin;

use App\Http\Controllers\Controller;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Services\Roles\Admin\ExternalPlayerAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function index(): Response
    {
        $this->authorize('viewAny', ExternalPlayer::class);

        return Inertia::render('Roles/Admin/ExternalPlayer/Index', [
            'players'    => $this->service->search(null, null, null),
            'statistics' => $this->service->statistics(),
        ]);
    }

    public function fetchList(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ExternalPlayer::class);

        $players = $this->service->search(
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
            $request->string('game_id')->toString() ?: null,
        );

        return response()->json([
            'players'    => $players,
            'statistics' => $this->service->statistics($request->string('game_id')->toString() ?: null),
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
