<?php

namespace App\Http\Controllers\Games\Bingo\History;

use App\Domains\Games\Bingo\Services\History\PlayerGameHistoryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class PlayerGameHistoryController extends Controller
{
    public function __construct(
        private readonly PlayerGameHistoryService $historyService,
    ) {
    }

    public function detail(Request $request, string $roomId) : \Illuminate\Http\JsonResponse
    {
        $userId = (string) Auth::id();
        $detail = $this->historyService->getDetail($userId, $roomId);

        return response()->json(['data' => $detail]);
    }

    public function index(Request $request) : Response
    {
        $userId = (string) Auth::id();
        // Limitar el tamaño de página evita que el historial dispare consultas
        // y cargas de memoria desproporcionadas por parámetros manipulados.
        $perPage = min(max($request->integer('per_page', 15), 1), 50);
        $page = max($request->integer('page', 1), 1);
        $search = $request->query('search') ?: null;
        $result = $request->query('result') ?: null;

        $paginator = $this->historyService->getHistory($userId, $perPage, $page, $search, $result);

        return Inertia::render('Games/Bingo/MyGames', [
            'title' => 'Mis Partidas de Bingo',
            'history' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
