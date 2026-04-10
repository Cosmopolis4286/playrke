<?php

namespace App\Http\Controllers\Games\Trivia\Report;

use App\Http\Controllers\Controller;
use App\Models\Games\Trivia\TriviaMulti;
use App\Models\Coin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de reportes de Trivia.
 *
 * Responsabilidades:
 * - Generar reportes de ganadores
 * - Generar reportes de ventas
 * - Mostrar estadísticas del juego
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class TriviaReportController extends Controller
{
    /**
     * Display the main trivia dashboard.
     */
    public function index(): Response
    {
        $stats = [
            'total_rooms' => TriviaMulti::count(),
            'active_rooms' => TriviaMulti::whereIn('status', [2, 3])->count(),
            'total_players' => DB::table('triv_multi_user')->count(),
            'total_winners' => DB::table('triv_multi_user')->where('lose_win', 1)->count(),
            'total_prizes' => DB::table('triv_multi_user')
                ->where('lose_win', 1)
                ->join('triv_multi', 'triv_multi.id', 'triv_multi_user.room')
                ->sum('triv_multi.prize')
        ];

        $recentRooms = TriviaMulti::with(['Coin'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Modules/Admin/Trivia/Index', [
            'title' => 'Panel de Trivia',
            'stats' => $stats,
            'recentRooms' => $recentRooms
        ]);
    }

    /**
     * Display winners list.
     */
    public function winners(Request $request): Response
    {
        $query = DB::table('triv_multi_user')
            ->join('users', 'users.id', 'triv_multi_user.user')
            ->join('triv_multi', 'triv_multi.id', 'triv_multi_user.room')
            ->join('coins', 'coins.id', 'triv_multi.coin')
            ->where('triv_multi_user.lose_win', 1)
            ->select('users.name', 'users.email', 'triv_multi.name as room_name', 
                    'coins.name as coin_name', 'triv_multi_user.created_at', 
                    'triv_multi.prize', 'triv_multi_user.finished_at');

        // Filtros
        if ($request->coin) {
            $coin = Coin::where('name', $request->coin)->first();
            if ($coin) {
                $query->where('triv_multi.coin', $coin->id);
            }
        }

        if ($request->date_from) {
            $query->whereDate('triv_multi_user.created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('triv_multi_user.created_at', '<=', $request->date_to);
        }

        $winners = $query->orderBy('triv_multi_user.created_at', 'desc')
            ->paginate(50);

        $coins = Coin::where('status', 1)->get();

        return Inertia::render('Modules/Admin/Trivia/Winners', [
            'title' => 'Ganadores de Trivia',
            'winners' => $winners,
            'filters' => $request->only(['coin', 'date_from', 'date_to']),
            'coins' => $coins
        ]);
    }

    /**
     * Display winners filtered by room/coin.
     */
    public function winnersRoom($coin): Response
    {
        $coinModel = Coin::where('name', $coin)->firstOrFail();
        
        $winners = DB::table('triv_multi_user')
            ->join('users', 'users.id', 'triv_multi_user.user')
            ->join('triv_multi', 'triv_multi.id', 'triv_multi_user.room')
            ->where('triv_multi_user.lose_win', 1)
            ->where('triv_multi.coin', $coinModel->id)
            ->select('users.name', 'users.email', 'triv_multi.name as room_name', 
                    'triv_multi_user.created_at', 'triv_multi.prize', 
                    'triv_multi_user.finished_at')
            ->orderBy('triv_multi_user.created_at', 'desc')
            ->paginate(50);

        return Inertia::render('Modules/Admin/Trivia/WinnersRoom', [
            'title' => "Ganadores de Trivia - {$coin}",
            'coin' => $coin,
            'winners' => $winners
        ]);
    }

    /**
     * Display cards sold report.
     */
    public function cardsSold(Request $request): Response
    {
        $query = DB::table('triv_multi_user')
            ->join('users', 'users.id', 'triv_multi_user.user')
            ->join('triv_multi', 'triv_multi.id', 'triv_multi_user.room')
            ->join('coins', 'coins.id', 'triv_multi.coin')
            ->select('users.name', 'users.email', 'triv_multi.name as room_name', 
                    'coins.name as coin_name', 'triv_multi_user.created_at', 
                    'triv_multi.prize');

        // Filtros
        if ($request->coin) {
            $coin = Coin::where('name', $request->coin)->first();
            if ($coin) {
                $query->where('triv_multi.coin', $coin->id);
            }
        }

        if ($request->date_from) {
            $query->whereDate('triv_multi_user.created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('triv_multi_user.created_at', '<=', $request->date_to);
        }

        $cardsSold = $query->orderBy('triv_multi_user.created_at', 'desc')
            ->paginate(50);

        $coins = Coin::where('status', 1)->get();

        return Inertia::render('Modules/Admin/Trivia/CardsSold', [
            'title' => 'Cartones Vendidos',
            'cardsSold' => $cardsSold,
            'filters' => $request->only(['coin', 'date_from', 'date_to']),
            'coins' => $coins
        ]);
    }

    /**
     * Display sales report.
     */
    public function salesReport(Request $request): Response
    {
        // Estadísticas generales por moneda
        $salesDataQuery = DB::table('triv_multi_user')
            ->join('triv_multi', 'triv_multi.id', 'triv_multi_user.room')
            ->join('coins', 'coins.id', 'triv_multi.coin')
            ->selectRaw('coins.name as coin_name, COUNT(*) as total_cards, SUM(triv_multi.prize) as total_sales');

        if ($request->date_from) {
            $salesDataQuery->whereDate('triv_multi_user.created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $salesDataQuery->whereDate('triv_multi_user.created_at', '<=', $request->date_to);
        }

        $salesData = $salesDataQuery
            ->groupBy('coins.id', 'coins.name')
            ->orderBy('total_sales', 'desc')
            ->get();

        // Ventas diarias
        $dailySalesQuery = DB::table('triv_multi_user')
            ->join('triv_multi', 'triv_multi.id', 'triv_multi_user.room')
            ->selectRaw('DATE(triv_multi_user.created_at) as date, COUNT(*) as total_cards, SUM(triv_multi.prize) as total_sales');

        if ($request->date_from) {
            $dailySalesQuery->whereDate('triv_multi_user.created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $dailySalesQuery->whereDate('triv_multi_user.created_at', '<=', $request->date_to);
        }

        $dailySales = $dailySalesQuery
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        $coins = Coin::where('status', 1)->get();

        return Inertia::render('Modules/Admin/Trivia/SalesReport', [
            'title' => 'Reporte de Ventas',
            'salesData' => $salesData,
            'dailySales' => $dailySales,
            'filters' => $request->only(['date_from', 'date_to']),
            'coins' => $coins
        ]);
    }

    /**
     * Display sales report filtered by room/coin.
     */
    public function salesReportRoom($coin, Request $request): Response
    {
        $coinModel = Coin::where('name', $coin)->firstOrFail();

        // Estadísticas de la moneda específica
        $salesDataQuery = DB::table('triv_multi_user')
            ->join('triv_multi', 'triv_multi.id', 'triv_multi_user.room')
            ->join('coins', 'coins.id', 'triv_multi.coin')
            ->where('triv_multi.coin', $coinModel->id)
            ->selectRaw('coins.name as coin_name, COUNT(*) as total_cards, SUM(triv_multi.prize) as total_sales');

        if ($request->date_from) {
            $salesDataQuery->whereDate('triv_multi_user.created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $salesDataQuery->whereDate('triv_multi_user.created_at', '<=', $request->date_to);
        }

        $salesData = $salesDataQuery
            ->groupBy('coins.id', 'coins.name')
            ->first();

        // Ventas diarias de la moneda específica
        $dailySalesQuery = DB::table('triv_multi_user')
            ->join('triv_multi', 'triv_multi.id', 'triv_multi_user.room')
            ->where('triv_multi.coin', $coinModel->id)
            ->selectRaw('DATE(triv_multi_user.created_at) as date, COUNT(*) as total_cards, SUM(triv_multi.prize) as total_sales');

        if ($request->date_from) {
            $dailySalesQuery->whereDate('triv_multi_user.created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $dailySalesQuery->whereDate('triv_multi_user.created_at', '<=', $request->date_to);
        }

        $dailySales = $dailySalesQuery
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        // Ventas detalladas
        $roomSalesQuery = DB::table('triv_multi_user')
            ->join('triv_multi', 'triv_multi.id', 'triv_multi_user.room')
            ->join('users', 'users.id', 'triv_multi_user.user')
            ->where('triv_multi.coin', $coinModel->id)
            ->select('users.name', 'triv_multi.name as room_name', 
                    'triv_multi_user.created_at', 'triv_multi.prize');

        if ($request->date_from) {
            $roomSalesQuery->whereDate('triv_multi_user.created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $roomSalesQuery->whereDate('triv_multi_user.created_at', '<=', $request->date_to);
        }

        $roomSales = $roomSalesQuery
            ->orderBy('triv_multi_user.created_at', 'desc')
            ->paginate(50);

        return Inertia::render('Modules/Admin/Trivia/SalesReportRoom', [
            'title' => "Reporte de Ventas - {$coin}",
            'coin' => $coin,
            'salesData' => $salesData,
            'dailySales' => $dailySales,
            'roomSales' => $roomSales,
            'filters' => $request->only(['date_from', 'date_to'])
        ]);
    }

    /**
     * Display the global leaderboard.
     * Accessible from sidebar menu.
     */
    public function leaderboard(): Response
    {
        $topPlayers = DB::table('triv_multi_user')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(*) as games_played'),
                DB::raw('SUM(CASE WHEN lose_win = 1 THEN 1 ELSE 0 END) as wins'),
                DB::raw('SUM(triv_multi.prize) as total_prizes')
            )
            ->join('users', 'users.id', 'triv_multi_user.user')
            ->join('triv_multi', 'triv_multi.id', 'triv_multi_user.room')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('wins')
            ->orderByDesc('total_prizes')
            ->limit(100)
            ->get();

        return Inertia::render('Trivia/Leaderboard', [
            'title' => 'Clasificación de Trivia',
            'topPlayers' => $topPlayers,
        ]);
    }

    /**
     * Display detailed statistics.
     * Accessible from sidebar menu.
     */
    public function stats(): Response
    {
        $stats = [
            'total_rooms' => TriviaMulti::count(),
            'active_rooms' => TriviaMulti::whereIn('status', [2, 3])->count(),
            'finished_rooms' => TriviaMulti::where('status', 4)->count(),
            'total_players' => DB::table('triv_multi_user')->count(),
            'unique_players' => DB::table('triv_multi_user')->distinct('user')->count('user'),
            'total_winners' => DB::table('triv_multi_user')->where('lose_win', 1)->count(),
            'total_prizes_distributed' => DB::table('triv_multi_user')
                ->where('lose_win', 1)
                ->join('triv_multi', 'triv_multi.id', 'triv_multi_user.room')
                ->sum('triv_multi.prize'),
            'games_by_coin' => DB::table('triv_multi')
                ->select('coins.name as coin', DB::raw('COUNT(*) as total'))
                ->join('coins', 'coins.id', 'triv_multi.coin')
                ->groupBy('coins.name')
                ->get(),
            'monthly_activity' => DB::table('triv_multi_user')
                ->select(
                    DB::raw('DATE_FORMAT(triv_multi_user.created_at, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as participants')
                )
                ->groupBy('month')
                ->orderByDesc('month')
                ->limit(12)
                ->get(),
        ];

        return Inertia::render('Modules/Admin/Trivia/Stats', [
            'title' => 'Estadísticas de Trivia',
            'stats' => $stats,
        ]);
    }
}
