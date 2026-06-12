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
     * Clasificación global de Trivia (nuevo sistema DDD).
     * Ruta: GET /trivia/leaderboard
     *
     * Fuente de verdad: trivia_scores (pgsql_trivia) + users (MySQL).
     * trivia_scores.player_id = users.id (referencia externa, sin FK).
     */
    public function leaderboard(Request $request): Response
    {
        $pgsql = DB::connection('pgsql_trivia');

        $period = $request->get('period', 'all'); // all | month | week

        // ── Agrupación de scores por jugador ─────────────────────────────────
        $scoresQuery = $pgsql->table('trivia_scores')
            ->whereNull('trivia_scores.deleted_at')
            ->selectRaw(implode(', ', [
                'player_id',
                'COUNT(*)                                    AS games_played',
                'SUM(final_score)                            AS total_score',
                'SUM(correct_answers)                        AS total_correct',
                'SUM(wrong_answers)                          AS total_wrong',
                'SUM(CASE WHEN is_winner THEN 1 ELSE 0 END) AS wins',
                'MAX(final_score)                            AS best_score',
                'MAX(max_streak)                             AS best_streak',
                'ROUND(AVG(average_response_time_ms))        AS avg_response_ms',
            ]));

        if ($period === 'week') {
            $scoresQuery->where('trivia_scores.created_at', '>=', now()->subWeek());
        } elseif ($period === 'month') {
            $scoresQuery->where('trivia_scores.created_at', '>=', now()->subMonth());
        }

        $scores = $scoresQuery
            ->groupBy('player_id')
            ->orderByDesc('total_score')
            ->limit(100)
            ->get();

        $globalStats = [
            'total_games'   => $pgsql->table('trivia_games')->whereNull('deleted_at')
                ->when($period === 'week',  fn($q) => $q->where('started_at', '>=', now()->subWeek()))
                ->when($period === 'month', fn($q) => $q->where('started_at', '>=', now()->subMonth()))
                ->where('status', 'finished')->count(),
            'total_players' => 0,
            'top_score'     => 0,
            'top_name'      => '-',
        ];

        if ($scores->isEmpty()) {
            return Inertia::render('Games/Trivia/Leaderboard', [
                'players'     => [],
                'period'      => $period,
                'globalStats' => $globalStats,
            ]);
        }

        // ── Obtener datos de usuarios desde MySQL ─────────────────────────────
        $playerIds = $scores->pluck('player_id')->all();

        $users = DB::table('users')
            ->whereIn('id', $playerIds)
            ->select('id', 'email', DB::raw("TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as display_name"))
            ->get()
            ->keyBy('id');

        // ── Obtener premios desde trivia_participants ──────────────────────────
        $prizes = $pgsql->table('trivia_participants')
            ->whereNull('deleted_at')
            ->whereIn('user_id', $playerIds)
            ->selectRaw('user_id, SUM(prize_won) AS total_prizes, COUNT(*) AS rooms_entered')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // ── Combinar y calcular métricas derivadas ────────────────────────────
        $players = $scores->values()->map(function ($row, $index) use ($users, $prizes) {
            $user   = $users->get($row->player_id);
            $prize  = $prizes->get($row->player_id);

            $totalAnswers = (int)$row->total_correct + (int)$row->total_wrong;
            $accuracy     = $totalAnswers > 0
                ? round(($row->total_correct / $totalAnswers) * 100, 1)
                : 0.0;

            $winRate = (int)$row->games_played > 0
                ? round(((int)$row->wins / (int)$row->games_played) * 100, 1)
                : 0.0;

            return [
                'rank'            => $index + 1,
                'player_id'       => $row->player_id,
                'name'            => ($user?->display_name ?: null) ?? 'Jugador #' . substr($row->player_id, 0, 6),
                'email'           => $user?->email ?? null,
                'games_played'    => (int)$row->games_played,
                'total_score'     => (int)$row->total_score,
                'total_correct'   => (int)$row->total_correct,
                'total_wrong'     => (int)$row->total_wrong,
                'wins'            => (int)$row->wins,
                'best_score'      => (int)$row->best_score,
                'best_streak'     => (int)$row->best_streak,
                'avg_response_ms' => (int)$row->avg_response_ms,
                'total_prizes'    => (float)($prize?->total_prizes ?? 0),
                'rooms_entered'   => (int)($prize?->rooms_entered ?? 0),
                'accuracy'        => $accuracy,
                'win_rate'        => $winRate,
            ];
        })->all();

        // Completar stats con datos ya calculados
        $globalStats['total_players'] = count($players);
        $globalStats['top_score']     = $players[0]['total_score'] ?? 0;
        $globalStats['top_name']      = $players[0]['name']        ?? '-';

        return Inertia::render('Games/Trivia/Leaderboard', [
            'players'     => $players,
            'period'      => $period,
            'globalStats' => $globalStats,
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

        return Inertia::render('Games/Trivia/Stats', [
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
