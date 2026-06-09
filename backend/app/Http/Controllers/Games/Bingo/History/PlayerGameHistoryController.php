<?php

namespace App\Http\Controllers\Games\Bingo\History;

use App\Http\Controllers\Controller;
use App\Services\Games\Bingo\History\PlayerGameHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class PlayerGameHistoryController extends Controller
{
    public function __construct(
        private readonly PlayerGameHistoryService $historyService,
    ) {}

    public function index(Request $request): Response
    {
        $userId  = (string) Auth::id();
        $perPage = (int) $request->query('per_page', 15);
        $page    = (int) $request->query('page', 1);

        $paginator = $this->historyService->getHistory($userId, $perPage, $page);

        return Inertia::render('Games/Bingo/MyGames', [
            'title'    => 'Mis Partidas de Bingo',
            'history'  => $paginator->items(),
            'pagination' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ]);
    }
}
