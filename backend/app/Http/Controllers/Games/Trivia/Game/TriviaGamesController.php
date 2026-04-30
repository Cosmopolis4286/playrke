<?php

declare(strict_types=1);

namespace App\Http\Controllers\Games\Trivia\Game;

use App\Http\Controllers\Controller;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador: Historial de Partidas de Trivia.
 *
 * Lista y detalla partidas desde trivia_games + trivia_scores + trivia_rooms.
 * Accesible desde el sidebar en /trivia/games.
 */
final class TriviaGamesController extends Controller
{
    /**
     * Listado paginado de partidas con filtros.
     * GET /trivia/games
     */
    public function index(Request $request): Response
    {
        $pgsql = DB::connection('pgsql_trivia');

        $status     = $request->get('status', '');
        $search     = $request->get('search', '');
        $dateFrom   = $request->get('date_from', '');
        $dateTo     = $request->get('date_to', '');
        $perPage    = 20;

        // ── Query principal: games + rooms ────────────────────────────────────
        $query = $pgsql->table('trivia_games as g')
            ->join('trivia_rooms as r', 'r.id', '=', 'g.room_id')
            ->whereNull('g.deleted_at')
            ->select(
                'g.id',
                'g.game_code',
                'g.status',
                'g.player_count',
                'g.total_questions',
                'g.current_question_index',
                'g.time_per_question',
                'g.winner_user_id',
                'g.started_at',
                'g.ended_at',
                'g.created_at',
                'r.id as room_id',
                'r.code as room_code',
                'r.title as room_title',
                'r.max_players'
            );

        if ($status !== '') {
            $query->where('g.status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('g.game_code', 'ilike', "%{$search}%")
                  ->orWhere('r.title', 'ilike', "%{$search}%")
                  ->orWhere('r.code', 'ilike', "%{$search}%");
            });
        }

        if ($dateFrom !== '') {
            $query->whereDate('g.started_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('g.started_at', '<=', $dateTo);
        }

        $games = $query->orderByDesc('g.created_at')->paginate($perPage)->withQueryString();

        // ── Enriquecer con ganadores (batch desde MySQL) ──────────────────────
        $winnerIds = collect($games->items())
            ->pluck('winner_user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $winners = [];
        if (!empty($winnerIds)) {
            $winners = DB::table('users')
                ->whereIn('id', $winnerIds)
                ->select('id', DB::raw("TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as display_name"))
                ->get()->pluck('display_name', 'id')->all();
        }

        // ── Enriquecer con top score por partida ──────────────────────────────
        $gameIds = collect($games->items())->pluck('id')->all();

        $topScores = [];
        if (!empty($gameIds)) {
            $topScores = $pgsql->table('trivia_scores')
                ->whereNull('deleted_at')
                ->whereIn('game_id', $gameIds)
                ->where('is_winner', true)
                ->select('game_id', 'player_id', 'final_score')
                ->get()
                ->keyBy('game_id')
                ->all();
        }

        // Inyectar datos en cada ítem
        $items = collect($games->items())->map(function ($game) use ($winners, $topScores) {
            $score = $topScores[$game->id] ?? null;
            return (object) array_merge((array) $game, [
                'winner_name'  => $game->winner_user_id ? ($winners[$game->winner_user_id] ?? 'Usuario #' . substr($game->winner_user_id, 0, 6)) : null,
                'winner_score' => $score ? (int) $score->final_score : null,
                'duration_min' => ($game->started_at && $game->ended_at)
                    ? round((strtotime($game->ended_at) - strtotime($game->started_at)) / 60, 1)
                    : null,
            ]);
        });

        // ── Stats para cards ──────────────────────────────────────────────────
        $stats = [
            'total'     => $pgsql->table('trivia_games')->whereNull('deleted_at')->count(),
            'playing'   => $pgsql->table('trivia_games')->whereNull('deleted_at')->where('status', 'playing')->count(),
            'finished'  => $pgsql->table('trivia_games')->whereNull('deleted_at')->where('status', 'finished')->count(),
            'cancelled' => $pgsql->table('trivia_games')->whereNull('deleted_at')->where('status', 'cancelled')->count(),
        ];

        return Inertia::render('Games/Trivia/Games', [
            'games'   => array_merge($games->toArray(), ['data' => $items->values()->all()]),
            'stats'   => $stats,
            'filters' => compact('status', 'search', 'dateFrom', 'dateTo'),
        ]);
    }

    /**
     * Detalle de una partida.
     * GET /trivia/games/{id}
     */
    public function show(string $id): Response
    {
        $pgsql = DB::connection('pgsql_trivia');

        $game = $pgsql->table('trivia_games as g')
            ->join('trivia_rooms as r', 'r.id', '=', 'g.room_id')
            ->whereNull('g.deleted_at')
            ->where('g.id', $id)
            ->select(
                'g.*',
                'r.code as room_code',
                'r.title as room_title',
                'r.max_players',
                'r.question_count as room_question_count'
            )
            ->first();

        abort_if(!$game, 404, 'Partida no encontrada');

        // ── Scores + nombres de jugadores ─────────────────────────────────────
        $scores = $pgsql->table('trivia_scores')
            ->whereNull('deleted_at')
            ->where('game_id', $id)
            ->orderBy('position')
            ->orderByDesc('final_score')
            ->get();

        $playerIds = $scores->pluck('player_id')->unique()->all();
        $users = DB::table('users')
            ->whereIn('id', $playerIds)
            ->select('id', DB::raw("TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as display_name"))
            ->get()->pluck('display_name', 'id')->all();

        $scoreboard = $scores->map(function ($s) use ($users) {
            return [
                'player_id'           => $s->player_id,
                'name'                => $users[$s->player_id] ?? 'Jugador #' . substr($s->player_id, 0, 6),
                'position'            => $s->position,
                'final_score'         => (int) $s->final_score,
                'correct_answers'     => (int) $s->correct_answers,
                'wrong_answers'       => (int) $s->wrong_answers,
                'max_streak'          => (int) $s->max_streak,
                'avg_response_ms'     => (int) $s->average_response_time_ms,
                'is_winner'           => (bool) $s->is_winner,
                'total_questions'     => (int) $s->total_questions,
            ];
        })->values()->all();

        // ── Preguntas jugadas en esta partida ─────────────────────────────────
        $questions = $pgsql->table('trivia_game_questions as gq')
            ->join('trivia_questions as q', 'q.id', '=', 'gq.question_id')
            ->whereNull('gq.deleted_at')
            ->where('gq.game_id', $id)
            ->orderBy('gq.order_index')
            ->select(
                'gq.order_index',
                'gq.points',
                'gq.time_limit_seconds',
                'gq.is_closed',
                'gq.sent_at',
                'gq.answered_at',
                'gq.question_duration_ms',
                'q.id as question_id',
                'q.question_text',
                'q.category',
                'q.difficulty',
                'q.options',
                'q.correct_option_index'
            )
            ->get()
            ->map(function ($q) {
                return array_merge((array) $q, [
                    'options' => is_string($q->options) ? json_decode($q->options, true) : $q->options,
                ]);
            })
            ->values()
            ->all();

        // ── Nombre del ganador ────────────────────────────────────────────────
        $winnerName = null;
        if ($game->winner_user_id) {
            $winnerName = $users[$game->winner_user_id]
                ?? DB::table('users')->where('id', $game->winner_user_id)
                    ->selectRaw("TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as display_name")
                    ->value('display_name')
                ?? 'Usuario #' . substr($game->winner_user_id, 0, 6);
        }

        $duration = ($game->started_at && $game->ended_at)
            ? round((strtotime($game->ended_at) - strtotime($game->started_at)) / 60, 1)
            : null;

        return Inertia::render('Games/Trivia/GameShow', [
            'game'        => array_merge((array) $game, [
                'winner_name' => $winnerName,
                'duration_min' => $duration,
            ]),
            'scoreboard'  => $scoreboard,
            'questions'   => $questions,
        ]);
    }
}
