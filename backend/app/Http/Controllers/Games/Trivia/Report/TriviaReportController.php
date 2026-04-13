<?php

namespace App\Http\Controllers\Games\Trivia\Report;

use App\Http\Controllers\Controller;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Models\TriviaQuestion;
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
            'total_rooms' => TriviaRoom::count(),
            'active_rooms' => TriviaRoom::whereIn('status', ['waiting', 'playing'])->count(),
            'total_players' => DB::connection('pgsql_trivia')->table('trivia_participants')->count(),
            'total_winners' => DB::connection('pgsql_trivia')->table('trivia_participants')->where('status', 'winner')->count(),
            'total_prizes' => DB::connection('pgsql_trivia')->table('trivia_participants')
                ->where('status', 'winner')
                ->sum('prize_won')
        ];

        $recentRooms = TriviaRoom::orderBy('created_at', 'desc')
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
        $query = DB::connection('pgsql_trivia')->table('trivia_participants')
            ->join('users', 'users.id', 'trivia_participants.user_id')
            ->join('trivia_rooms', 'trivia_rooms.id', 'trivia_participants.room_id')
            ->where('trivia_participants.status', 'winner')
            ->select('users.name', 'users.email', 'trivia_rooms.title as room_name',
                    'trivia_participants.created_at',
                    'trivia_participants.prize_won', 'trivia_participants.ended_at as finished_at');

        // Filtros
        if ($request->currency_id) {
            $query->where('trivia_rooms.entry_currency_id', $request->currency_id);
        }

        if ($request->date_from) {
            $query->whereDate('trivia_participants.created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('trivia_participants.created_at', '<=', $request->date_to);
        }

        $winners = $query->orderBy('trivia_participants.created_at', 'desc')
            ->paginate(50);

        $currencies = Currency::where('status', 1)->get();

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
     * Estadísticas globales del sistema de Trivia (nuevo sistema DDD).
     * Ruta: GET /trivia/stats
     */
    public function stats(): Response
    {
        $pgsql = DB::connection('pgsql_trivia');

        // ── Salas ────────────────────────────────────────────────────────────
        $roomStats = [
            'total'     => TriviaRoom::count(),
            'waiting'   => TriviaRoom::where('status', 'waiting')->count(),
            'playing'   => TriviaRoom::where('status', 'playing')->count(),
            'finished'  => TriviaRoom::where('status', 'closed')->count(),
            'cancelled' => TriviaRoom::where('status', 'cancelled')->count(),
        ];

        // ── Juegos ───────────────────────────────────────────────────────────
        $gameStats = [
            'total'    => $pgsql->table('trivia_games')->whereNull('deleted_at')->count(),
            'finished' => $pgsql->table('trivia_games')->where('status', 'finished')->whereNull('deleted_at')->count(),
            'playing'  => $pgsql->table('trivia_games')->where('status', 'playing')->whereNull('deleted_at')->count(),
        ];

        // ── Preguntas ────────────────────────────────────────────────────────
        $questionStats = [
            'total'  => TriviaQuestion::count(),
            'active' => TriviaQuestion::where('is_active', true)->count(),
            'by_difficulty' => TriviaQuestion::selectRaw('difficulty, COUNT(*) as total')
                ->groupBy('difficulty')
                ->pluck('total', 'difficulty'),
            'categories' => TriviaQuestion::selectRaw('category, COUNT(*) as total')
                ->whereNotNull('category')
                ->groupBy('category')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
        ];

        // ── Participantes ────────────────────────────────────────────────────
        $participantStats = [
            'total'         => $pgsql->table('trivia_participants')->whereNull('deleted_at')->count(),
            'unique_users'  => $pgsql->table('trivia_participants')->whereNull('deleted_at')->distinct()->count('user_id'),
            'winners'       => $pgsql->table('trivia_participants')->where('status', 'winner')->whereNull('deleted_at')->count(),
            'abandoned'     => $pgsql->table('trivia_participants')->where('status', 'abandoned')->whereNull('deleted_at')->count(),
            'total_prizes'  => (float) $pgsql->table('trivia_participants')->whereNull('deleted_at')->sum('prize_won'),
        ];

        // ── Scores (top 10 jugadores por puntuación total) ───────────────────
        $topPlayers = $pgsql->table('trivia_scores')
            ->whereNull('trivia_scores.deleted_at')
            ->selectRaw('player_id, SUM(final_score) as total_score, SUM(correct_answers) as correct, COUNT(*) as games_played, SUM(CASE WHEN is_winner THEN 1 ELSE 0 END) as wins')
            ->groupBy('player_id')
            ->orderByDesc('total_score')
            ->limit(10)
            ->get();

        // ── Actividad mensual (últimos 6 meses, formato ISO para PostgreSQL) ──
        $monthlyActivity = $pgsql->table('trivia_games')
            ->whereNull('deleted_at')
            ->whereNotNull('started_at')
            ->selectRaw("TO_CHAR(started_at, 'YYYY-MM') as month, COUNT(*) as games, SUM(player_count) as players")
            ->groupByRaw("TO_CHAR(started_at, 'YYYY-MM')")
            ->orderByDesc('month')
            ->limit(6)
            ->get();

        // ── Tiempo promedio de respuesta (desde trivia_scores) ───────────────
        $avgResponseMs = (int) $pgsql->table('trivia_scores')
            ->whereNull('deleted_at')
            ->where('average_response_time_ms', '>', 0)
            ->avg('average_response_time_ms');

        // ── Tasa de aciertos global ──────────────────────────────────────────
        $totalCorrect = (int) $pgsql->table('trivia_scores')->whereNull('deleted_at')->sum('correct_answers');
        $totalAnswers = (int) $pgsql->table('trivia_scores')->whereNull('deleted_at')
            ->selectRaw('SUM(correct_answers + wrong_answers) as t')
            ->value('t');
        $accuracyRate = $totalAnswers > 0 ? round(($totalCorrect / $totalAnswers) * 100, 1) : 0;

        return Inertia::render('Trivia/Stats', [
            'roomStats'        => $roomStats,
            'gameStats'        => $gameStats,
            'questionStats'    => $questionStats,
            'participantStats' => $participantStats,
            'topPlayers'       => $topPlayers,
            'monthlyActivity'  => $monthlyActivity,
            'avgResponseMs'    => $avgResponseMs,
            'accuracyRate'     => $accuracyRate,
        ]);
    }
}
