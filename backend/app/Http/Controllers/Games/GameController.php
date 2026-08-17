<?php

namespace App\Http\Controllers\Games;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Services\Games\Common\GameMode\GameModeService;
use App\Services\Games\Common\Jackpot\JackpotTypeService;
use App\Services\Games\Common\PrizePool\PrizePoolTypeService;
use App\Services\Games\Common\Room\RoomTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function __construct(
        private readonly GameModeService $gameModeService,
        private readonly RoomTypeService $roomTypeService,
        private readonly PrizePoolTypeService $prizePoolTypeService,
        private readonly JackpotTypeService $jackpotTypeService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Game::class);

        $games = Game::orderBy('name')->get();

        return Inertia::render('Games/Index', [
            'games'          => $games,
            'gameModes'      => $this->gameModeService->listByGame(),
            'roomTypes'      => $this->roomTypeService->list(),
            'prizePoolTypes' => $this->prizePoolTypeService->listByGame(),
            'jackpotTypes'   => $this->jackpotTypeService->listByGame(),
            'roleUser'       => Auth::user()?->roles?->first()?->prefix ?? 'guest',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Game::class);

        $validated = $request->validate([
            'name'   => 'required|string|max:50|unique:games,name',
            'status' => 'required|in:ACTIVE,INACTIVE,MAINTENANCE',
        ]);

        $validated['name'] = strtoupper(trim($validated['name']));

        $game = Game::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Juego creado exitosamente',
            'data'    => $game,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $game = Game::findOrFail($id);
        $this->authorize('view', $game);

        return response()->json([
            'success' => true,
            'data'    => $game,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $game = Game::findOrFail($id);
        $this->authorize('update', $game);

        $validated = $request->validate([
            'name'   => 'required|string|max:50|unique:games,name,' . $id,
            'status' => 'required|in:ACTIVE,INACTIVE,MAINTENANCE',
        ]);

        $validated['name'] = strtoupper(trim($validated['name']));

        $game->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Juego actualizado exitosamente',
            'data'    => $game->fresh(),
        ]);
    }

    public function delete(string $id): JsonResponse
    {
        $game = Game::findOrFail($id);
        $this->authorize('delete', $game);

        $game->delete();

        return response()->json([
            'success' => true,
            'message' => 'Juego eliminado exitosamente',
        ]);
    }

    public function fetchList(Request $request): JsonResponse
    {
        $this->authorize('fetchList', Game::class);

        $games = Game::when($request->get('status'), fn($q, $s) => $q->byStatus($s))
            ->when($request->get('name'), fn($q, $n) => $q->byName($n))
            ->orderedByName()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $games,
        ]);
    }

    public function toggleStatus(string $id): JsonResponse
    {
        $game = Game::findOrFail($id);
        $this->authorize('update', $game);

        $next = match ($game->status) {
            'ACTIVE'      => 'INACTIVE',
            'INACTIVE'    => 'ACTIVE',
            'MAINTENANCE' => 'ACTIVE',
            default       => 'INACTIVE',
        };

        $game->update(['status' => $next]);

        return response()->json([
            'success' => true,
            'message' => 'Estado del juego actualizado',
            'data'    => $game->fresh(),
        ]);
    }
}
