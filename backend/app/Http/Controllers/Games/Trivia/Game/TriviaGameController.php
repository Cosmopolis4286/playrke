<?php

namespace App\Http\Controllers\Games\Trivia\Game;

use App\Http\Controllers\Controller;
use App\Traits\Users\UserTrait;
use App\Domains\Games\Trivia\Services\RoomService;
use App\Domains\Games\Trivia\Actions\JoinRoomAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Controlador de juego de Trivia.
 *
 * Responsabilidades:
 * - Manejar la lógica del juego para los jugadores
 * - Gestionar la participación en salas
 * - Controlar el flujo del juego individual
 *
 * NO contiene lógica de administración ni reportes.
 */
final class TriviaGameController extends Controller
{
    use UserTrait;

    /**
     * Display available trivia rooms for players.
     */
    public function rooms($coinSym): Response
    {
        $user = $this->user(['id']);
        $wallet = $this->wallet($user->id);
        
        $coin = DB::table('coins')->where('name', $coinSym)->first();
        if (!$coin) {
            abort(404, 'Moneda no encontrada');
        }

        $trivMulti = TriviaMulti::with(['Coin'])
            ->where('coin', $coin->id)
            ->whereIn('status', [1, 2, 3])
            ->get();

        $date = now()->toDateTimeString();

        return Inertia::render('Modules/User/Trivia/Rooms', [
            'user' => $user,
            'wallet' => $wallet,
            'date' => $date,
            'coin' => $coinSym,
            'trivMulti' => $trivMulti,
            'coinRoom' => $coin
        ]);
    }

    /**
     * Unirse a una sala de trivia (nuevo sistema).
     * POST /trivia-game/room/{id}/join
     */
    public function room(Request $request, $id)
    {
        $user     = Auth::user();
        $roomService = app(RoomService::class);
        $joinAction = app(JoinRoomAction::class);

        $room = \App\Domains\Games\Trivia\Models\TriviaRoom::findOrFail($id);

        if (!in_array($room->status, ['waiting', 'playing'])) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'La sala ya no está disponible'], 422);
            }
            return back()->with('error', 'La sala ya no está disponible.');
        }

        $roomId  = (string) $room->id;
        $userId  = (string) $user->id;
        $displayName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->username ?? 'Jugador');

        // Verificar si ya está registrado
        $exists = DB::connection('pgsql_trivia')
            ->table('trivia_participants')
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success'      => true,
                    'already_joined' => true,
                    'player'       => [
                        'id'       => $userId,
                        'name'     => $displayName,
                        'roomCode' => $room->code,
                    ],
                ]);
            }
            return redirect()->route('trivia.room.wait', ['code' => $room->code]);
        }

        // Usar JoinRoomAction para manejar descuento de wallet y unión
        try {
            $result = $joinAction->execute($roomId, $userId, $displayName);

            if ($request->expectsJson()) {
                return response()->json([
                    'success'        => true,
                    'already_joined' => false,
                    'player'         => [
                        'id'       => $userId,
                        'name'     => $displayName,
                        'roomCode' => $room->code,
                    ],
                    'message'        => $result['message'] ?? 'Te has unido a la sala exitosamente',
                ]);
            }

            return redirect()->route('trivia.room.wait', ['code' => $room->code]);

        } catch (RuntimeException $e) {
            // Error de saldo insuficiente o wallet no encontrada
            $statusCode = str_contains($e->getMessage(), 'Saldo insuficiente') ? 402 : 422;

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $statusCode);
            }

            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            // Cualquier otro error (incluyendo Pusher/broadcasting)
            // No debe impedir que el usuario se una a la sala
            if ($request->expectsJson()) {
                return response()->json([
                    'success'        => true,
                    'already_joined' => false,
                    'player'         => [
                        'id'       => $userId,
                        'name'     => $displayName,
                        'roomCode' => $room->code,
                    ],
                    'warning'        => 'Te uniste pero hubo un error de conexión en tiempo real',
                ]);
            }

            return redirect()->route('trivia.room.wait', ['code' => $room->code]);
        }
    }

    /**
     * Display the game interface. (Legacy — sistema deprecated)
     */
    public function inGame($id): Response
    {
        $user = $this->user(['id']);
        $wallet = $this->wallet($user->id);
        $game = \App\Models\Games::find(2);

        $room = \App\Models\Games\Trivia\TriviaMulti::findOrFail($id);
        $cupsQuest = \App\Models\Games\Trivia\TriviaMultiCups::where('room', $id)->get();
        $extrasUser = \App\Models\ExtrasUser::where('user', $user->id)->get();

        $infoGame = [
            'game' => $game,
            'room' => $room,
            'user_in_room' => DB::table('triv_multi_user')
                ->where('room', $id)
                ->where('user', $user->id)
                ->first()
        ];

        $sounds = \App\Models\GameSounds::whereIn('id_game', [2, 0])->get();

        return Inertia::render('Modules/User/Trivia/RmMulti', [
            'wallet' => $wallet,
            'game' => $game,
            'cupsQuest' => $cupsQuest,
            'extrasUser' => $extrasUser,
            'infoGame' => $infoGame,
            'sounds' => $sounds
        ]);
    }

    /**
     * Get timer information for a room. (Legacy — sistema deprecated)
     */
    public function timer($id)
    {
        return \App\Models\Games\Trivia\TriviaMulti::select('id', 'status', 'fecha', 'hora')
            ->where('id', $id)->whereIn('status', [2, 3])->get();
    }

    /**
     * Display individual trivia games.
     */
    public function individual($id): Response
    {
        $user = $this->user(['id']);
        $wallet = $this->wallet($user->id);
        
        $cdin = DB::table('triv_indv_cdin')->where('id_user', $user)
            ->orderBy('id', 'desc')->first();
            
        if ($cdin == null || $cdin->start == 2) {
            return redirect()->route('trivia.index');
        }
        
        $quest = $this->rand_quest($cdin->id);
        if ($cdin->start == 0) {
            DB::table('triv_indv_cdin')->where('id', $cdin->id)->update(['start' => 1]);
        }

        return Inertia::render('Modules/User/Trivia/Individual', [
            'wallet' => $wallet,
            'cdin' => $cdin,
            'quest' => $quest
        ]);
    }

    /**
     * Check individual trivia answer.
     */
    public function checkIndividual(Request $request)
    {
        $user = auth()->user()->id;
        $answer = $request->input('answer');
        $questionId = $request->input('question_id');
        
        // Lógica para verificar la respuesta
        $correctAnswer = DB::table('triv_indv_quest')
            ->where('id', $questionId)
            ->value('answer');
            
        $isCorrect = strtolower($answer) === strtolower($correctAnswer);
        
        return response()->json([
            'correct' => $isCorrect,
            'message' => $isCorrect ? '¡Correcto!' : 'Incorrecto, intenta de nuevo.'
        ]);
    }

    /**
     * Get room information for AJAX requests. (Legacy — sistema deprecated)
     */
    public function roomInfo($id)
    {
        $room = \App\Models\Games\Trivia\TriviaMulti::with(['Coin', 'Questions'])
            ->findOrFail($id);

        $players = DB::table('triv_multi_user')
            ->join('users', 'users.id', 'triv_multi_user.user')
            ->where('triv_multi_user.room', $id)
            ->select('users.name', 'users.email', 'triv_multi_user.*')
            ->get();

        return response()->json([
            'room' => $room,
            'players' => $players
        ]);
    }

    /**
     * Leave a trivia room.
     */
    public function leaveRoom($id)
    {
        $user = auth()->user()->id;
        
        DB::table('triv_multi_user')
            ->where('room', $id)
            ->where('user', $user)
            ->update([
                'lose_win' => 6, // Abandonó
                'finished_at' => now()
            ]);

        return redirect()->route('trivia-games.rooms', ['coin' => 'rke']);
    }

    /**
     * Get random question for individual trivia.
     */
    private function rand_quest($id)
    {
        $user = auth()->user()->id;
        $cdin = DB::table('triv_indv_cdin')->where('id_user', $user)->where('id', $id)->first();
        
        // Lógica para obtener pregunta aleatoria
        $question = DB::table('triv_indv_quest')
            ->whereNotIn('id', function($query) use ($id) {
                $query->select('id_quest')
                    ->from('triv_indv_user')
                    ->where('id_triv', $id);
            })
            ->inRandomOrder()
            ->first();

        return $question;
    }

    /**
     * Estado de sala en JSON para polling desde WaitingRoom.
     * Ruta: GET /trivia/room/{code}/state
     */
    public function roomState(string $code): \Illuminate\Http\JsonResponse
    {
        $room = \App\Domains\Games\Trivia\Models\TriviaRoom::where('code', $code)
            ->whereIn('status', ['waiting', 'playing', 'closed'])
            ->firstOrFail();

        // Participantes desde trivia_participants + nombre desde MySQL users
        $participants = DB::connection('pgsql_trivia')
            ->table('trivia_participants')
            ->where('room_id', $room->id)
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['abandoned'])
            ->select('user_id', 'status')
            ->get();

        $userIds = $participants->pluck('user_id')->all();
        $names   = $userIds
            ? DB::table('users')->whereIn('id', $userIds)
                ->select('id', DB::raw("TRIM(CONCAT(first_name, ' ', COALESCE(last_name, ''))) as display_name"))
                ->get()->pluck('display_name', 'id')->all()
            : [];

        $players = $participants->map(function ($p) use ($names, $room) {
            return [
                'id'       => $p->user_id,
                'name'     => $names[$p->user_id] ?? 'Jugador',
                'roomCode' => $room->code,
                'joinedAt' => $p->registered_at ?? null,
            ];
        })->values()->all();

        return response()->json([
            'status'  => $room->status,
            'players' => $players,
        ]);
    }

    /**
     * Sala de espera de una sala específica (WaitingRoom).
     * Ruta: GET /trivia/room/{code}/wait
     */
    public function waitingRoom(string $code): Response
    {
        $roomService = app(RoomService::class);
        $user        = Auth::user();

        $room = \App\Domains\Games\Trivia\Models\TriviaRoom::where('code', $code)
            ->whereIn('status', ['waiting', 'playing'])
            ->firstOrFail();

        $roomId  = (string) $room->id;
        $roomDto = $roomService->getRoom($roomId);

        // ── Jugadores desde trivia_participants (fuente de verdad — no Redis) ──
        $participants = DB::connection('pgsql_trivia')
            ->table('trivia_participants')
            ->where('room_id', $roomId)
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['abandoned'])
            ->orderBy('registered_at')
            ->select('user_id', 'status', 'registered_at')
            ->get();

        $userIds = $participants->pluck('user_id')->all();
        $names   = $userIds
            ? DB::table('users')
                ->whereIn('id', $userIds)
                ->select('id', DB::raw("TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as display_name"))
                ->get()->pluck('display_name', 'id')->all()
            : [];

        $initialPlayers = $participants->map(fn($p) => [
            'id'           => $p->user_id,
            'name'         => $names[$p->user_id] ?? 'Jugador',
            'roomCode'     => $roomDto->code,
            'joinedAt'     => $p->registered_at, // ISO string para el frontend
        ])->values()->all();

        // ── Detectar si el usuario ya está inscrito ───────────────────────────
        $alreadyJoined = collect($initialPlayers)
            ->contains('id', (string) $user->id);

        // ── Obtener símbolo de moneda de entrada ──────────────────────────────
        $entryCurrencySymbol = null;
        if ($room->entry_currency_id) {
            $currency = DB::table('currencies')->where('id', $room->entry_currency_id)->first();
            $entryCurrencySymbol = $currency?->symbol;
        }

        // ── Obtener wallet del usuario para la moneda de entrada ────────────────
        $userWallet = null;
        if ($room->entry_currency_id) {
            $wallet = $user->wallets()
                ->where('currency_id', $room->entry_currency_id)
                ->where('status', 'ACTIVE')
                ->with('currency')
                ->first();

            if ($wallet) {
                $userWallet = [
                    'balance'    => (float) $wallet->balance,
                    'symbol'     => $wallet->currency?->symbol ?? $entryCurrencySymbol ?? '',
                    'currencyId' => (string) $wallet->currency_id,
                ];
            }
        }

        return Inertia::render('Games/Trivia/WaitingRoom', [
            'room' => [
                'id'                   => (string) $roomDto->roomId,
                'code'                 => $roomDto->code,
                'title'                => $roomDto->title,
                'description'          => $room->description,
                'category'             => $room->category ?? null,
                'hostId'               => $roomDto->hostId,
                'status'               => $roomDto->status,
                'maxPlayers'           => $roomDto->maxPlayers,
                'currentPlayers'       => count($initialPlayers),
                'questionCount'        => $room->question_count,
                'timePerQuestion'      => $room->time_per_question,
                'entryFee'             => $room->entry_fee ? (float) $room->entry_fee : null,
                'entryCurrencyId'      => $room->entry_currency_id,
                'entryCurrencySymbol'  => $entryCurrencySymbol,
                'prizeAmount'          => $room->prize_amount ? (float) $room->prize_amount : null,
                'prizeCurrencyId'      => $room->prize_currency_id,
                'prizeType'            => $room->prize_type,
                'createdAt'            => $room->created_at->toISOString(),
                'updatedAt'            => $room->updated_at->toISOString(),
            ],
            'initialPlayers' => $initialPlayers,
            'alreadyJoined'  => $alreadyJoined,
            'currentUserId'  => (string) $user->id,
            'userWallet'     => $userWallet,
        ]);
    }

    /**
     * Display the trivia lobby for players.
     * Muestra todas las salas en espera/jugando, agrupadas por categoría.
     * GET /trivia/lobby
     */
    public function lobby(Request $request): Response
    {
        $pgsql = DB::connection('pgsql_trivia');

        $search   = $request->get('search', '');
        $category = $request->get('category', '');

        // ── Salas activas ─────────────────────────────────────────────────────
        $query = $pgsql->table('trivia_rooms')
            ->whereNull('deleted_at')
            ->whereIn('status', ['waiting', 'playing'])
            ->orderByRaw("CASE WHEN status = 'waiting' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%")
                  ->orWhere('category', 'ilike', "%{$search}%");
            });
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        $rooms = $query->select(
            'id', 'code', 'title', 'description', 'category',
            'status', 'max_players', 'current_players',
            'question_count', 'time_per_question', 'created_at',
            'entry_fee', 'entry_currency_id',
            'prize_amount', 'prize_type', 'prize_currency_id'
        )->get();

        // ── Resolver símbolos de moneda (MySQL currencies) ────────────────────
        $currencyIds = collect($rooms)
            ->flatMap(fn($r) => array_filter([$r->entry_currency_id, $r->prize_currency_id]))
            ->unique()
            ->values()
            ->all();

        $currencySymbols = [];
        if (!empty($currencyIds)) {
            $currencySymbols = DB::table('currencies')
                ->whereIn('id', $currencyIds)
                ->pluck('symbol', 'id')
                ->all();
        }

        $rooms = $rooms->map(function ($room) use ($currencySymbols) {
            return array_merge((array) $room, [
                'entry_currency_symbol' => $room->entry_currency_id
                    ? ($currencySymbols[$room->entry_currency_id] ?? null)
                    : null,
                'prize_currency_symbol' => $room->prize_currency_id
                    ? ($currencySymbols[$room->prize_currency_id] ?? null)
                    : null,
            ]);
        });

        // ── Categorías disponibles para filtro ────────────────────────────────
        $categories = $pgsql->table('trivia_rooms')
            ->whereNull('deleted_at')
            ->whereIn('status', ['waiting', 'playing'])
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        // ── Stats rápidas ─────────────────────────────────────────────────────
        $stats = [
            'waiting' => $pgsql->table('trivia_rooms')->whereNull('deleted_at')->where('status', 'waiting')->count(),
            'playing' => $pgsql->table('trivia_rooms')->whereNull('deleted_at')->where('status', 'playing')->count(),
            'total'   => $pgsql->table('trivia_rooms')->whereNull('deleted_at')->whereIn('status', ['waiting', 'playing'])->count(),
        ];

        return Inertia::render('Games/Trivia/Lobby', [
            'rooms'      => $rooms,
            'categories' => $categories,
            'stats'      => $stats,
            'filters'    => compact('search', 'category'),
        ]);
    }

    /**
     * Página de juego activo - "El Poder del Saber".
     * Interfaz de juego inmersiva con el diseño visual del concurso.
     * Ruta: GET /trivia/play/{gameId}
     */
    public function play(string $gameId): Response
    {
        $user = Auth::user();
        $pgsql = DB::connection('pgsql_trivia');

        // Obtener información del juego
        $game = $pgsql->table('trivia_games')
            ->where('id', $gameId)
            ->first();

        if (!$game) {
            abort(404, 'Juego no encontrado');
        }

        // Obtener información de la sala
        $room = $pgsql->table('trivia_rooms')
            ->where('id', $game->room_id)
            ->first();

        // Calcular el pote total
        $potAmount = $room->prize_amount ?? (
            ($room->entry_fee ?? 0) * ($room->current_players ?? 0)
        );

        // Obtener la pregunta actual (si existe)
        $currentQuestion = $pgsql->table('trivia_questions')
            ->where('game_id', $gameId)
            ->where('status', 'sent')
            ->orderBy('sent_at', 'desc')
            ->first();

        $question = null;
        if ($currentQuestion) {
            $questionData = $pgsql->table('trivia_question_bank')
                ->where('id', $currentQuestion->question_id)
                ->first();

            if ($questionData) {
                $question = [
                    'id' => $questionData->id,
                    'text' => $questionData->question_text,
                    'image' => $questionData->image_url ?? null,
                    'options' => $questionData->options ?? [],
                    'timeLimit' => $questionData->time_limit_seconds ?? $room->time_per_question ?? 20,
                    'category' => $questionData->category ?? null,
                ];
            }
        }

        // Obtener posición del jugador
        $participant = $pgsql->table('trivia_participants')
            ->where('room_id', $game->room_id)
            ->where('user_id', (string) $user->id)
            ->first();

        return Inertia::render('Games/Trivia/Play', [
            'room' => [
                'id' => (string) $room->id,
                'code' => $room->code,
                'title' => $room->title,
                'potAmount' => (float) $potAmount,
                'currentPlayers' => $room->current_players ?? 0,
                'maxPlayers' => $room->max_players ?? 100,
                'cupsAvailable' => $room->cups_available ?? 0,
                'cupsTotal' => $room->max_players ?? 0,
            ],
            'game' => [
                'id' => $gameId,
                'currentQuestion' => $game->current_question_index ?? 1,
                'totalQuestions' => $room->question_count ?? 10,
            ],
            'question' => $question,
            'player' => [
                'id' => (string) $user->id,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->username ?? 'Jugador'),
                'avatar' => $user->avatar ?? null,
            ],
        ]);
    }

    /**
     * Página de resultados del juego - "El Poder del Saber".
     * Muestra el desempeño del jugador y la clasificación final.
     * Ruta: GET /trivia/play/{gameId}/results
     */
    public function playResults(string $gameId): Response
    {
        $user = Auth::user();
        $pgsql = DB::connection('pgsql_trivia');

        // Obtener información del juego
        $game = $pgsql->table('trivia_games')
            ->where('id', $gameId)
            ->first();

        if (!$game) {
            abort(404, 'Juego no encontrado');
        }

        // Obtener información de la sala
        $room = $pgsql->table('trivia_rooms')
            ->where('id', $game->room_id)
            ->first();

        // Obtener score del jugador actual
        $playerScore = $pgsql->table('trivia_scores')
            ->where('game_id', $gameId)
            ->where('player_id', (string) $user->id)
            ->first();

        // Obtener todos los scores para el ranking
        $allScores = $pgsql->table('trivia_scores')
            ->where('game_id', $gameId)
            ->orderBy('final_score', 'desc')
            ->orderBy('correct_answers', 'desc')
            ->get();

        // Calcular ranking del jugador
        $playerRank = 0;
        $totalPlayers = $allScores->count();

        foreach ($allScores as $index => $score) {
            if ($score->player_id === (string) $user->id) {
                $playerRank = $index + 1;
                break;
            }
        }

        // Obtener nombres de jugadores
        $playerIds = $allScores->pluck('player_id')->unique()->all();
        $playerNames = [];
        if (!empty($playerIds)) {
            $playerNames = DB::table('users')
                ->whereIn('id', $playerIds)
                ->select('id', DB::raw("TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as display_name"))
                ->get()
                ->pluck('display_name', 'id')
                ->all();
        }

        // Construir top players (top 10)
        $topPlayers = $allScores->take(10)->map(function ($score, $index) use ($playerNames, $user) {
            return [
                'position' => $index + 1,
                'name' => $playerNames[$score->player_id] ?? 'Jugador',
                'score' => $score->final_score ?? 0,
                'correctAnswers' => $score->correct_answers ?? 0,
                'isCurrentPlayer' => $score->player_id === (string) $user->id,
            ];
        })->all();

        // Calcular premio ganado (si aplica)
        $wonPrize = null;
        if ($playerRank <= 3 && $room->prize_type === 'winner_takes_all') {
            // Lógica de premios según configuración
            $prizeDistribution = match($playerRank) {
                1 => 0.5,  // 50% para el primer lugar
                2 => 0.3,  // 30% para el segundo
                3 => 0.2,  // 20% para el tercero
                default => 0,
            };
            $wonPrize = ($room->prize_amount ?? 0) * $prizeDistribution;
        }

        return Inertia::render('Games/Trivia/PlayResults', [
            'game' => [
                'id' => $gameId,
                'roomTitle' => $room->title,
                'totalQuestions' => $room->question_count ?? 10,
                'correctAnswers' => $playerScore->correct_answers ?? 0,
                'finalScore' => $playerScore->final_score ?? 0,
                'rank' => $playerRank,
                'totalPlayers' => $totalPlayers,
                'wonPrize' => $wonPrize,
            ],
            'player' => [
                'id' => (string) $user->id,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->username ?? 'Jugador'),
                'avatar' => $user->avatar ?? null,
            ],
            'topPlayers' => $topPlayers,
        ]);
    }
}
