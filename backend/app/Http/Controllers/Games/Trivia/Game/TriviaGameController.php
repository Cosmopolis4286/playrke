<?php

namespace App\Http\Controllers\Games\Trivia\Game;

use App\Http\Controllers\Controller;
use App\Traits\Users\UserTrait;
use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
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
     * Unirse a una sala de trivia.
     * POST /trivia-game/room/{id}/join
     */
    public function join(Request $request, string $id): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user       = Auth::user();
        $joinAction = app(JoinRoomAction::class);

        $room = \App\Domains\Games\Trivia\Models\TriviaRoom::findOrFail($id);

        if (! in_array($room->status, ['waiting', 'playing'])) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'La sala ya no está disponible.'], 422)
                : back()->with('error', 'La sala ya no está disponible.');
        }

        $roomId      = (string) $room->id;
        $userId      = (string) $user->id;
        $displayName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->username ?? 'Jugador');

        // Verificar si ya está registrado (respuesta rápida, sin cobro)
        $alreadyJoined = DB::connection('pgsql_trivia')
            ->table('trivia_participants')
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->exists();

        if ($alreadyJoined) {
            return $request->expectsJson()
                ? response()->json([
                    'success'        => true,
                    'already_joined' => true,
                    'player'         => ['id' => $userId, 'name' => $displayName, 'roomCode' => $room->code],
                ])
                : redirect()->route('trivia.room.wait', ['code' => $room->code]);
        }

        // Validar equipo si el modo lo requiere
        $teamId = $request->input('team_id') ?: null;
        if ($room->usesTeams() && ! $teamId) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Debes seleccionar un equipo para unirte.'], 422)
                : back()->with('error', 'Debes seleccionar un equipo para unirte.');
        }

        try {
            $result = $joinAction->execute($roomId, $userId, $displayName, [], $teamId);

            return $request->expectsJson()
                ? response()->json([
                    'success'        => true,
                    'already_joined' => false,
                    'player'         => ['id' => $userId, 'name' => $displayName, 'roomCode' => $room->code],
                    'team_id'        => $result['team_id'] ?? null,
                    'message'        => $result['message'] ?? 'Te has unido a la sala exitosamente.',
                ])
                : redirect()->route('trivia.room.wait', ['code' => $room->code]);

        } catch (RuntimeException $e) {
            $status = str_contains($e->getMessage(), 'Saldo insuficiente') ? 402 : 422;

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], $status)
                : back()->with('error', $e->getMessage());

        } catch (\Throwable $e) {
            // Broadcasting u otro error no crítico — el join en BD ya ocurrió
            \Illuminate\Support\Facades\Log::warning('[TriviaGameController@join] ' . $e->getMessage());

            return $request->expectsJson()
                ? response()->json([
                    'success'        => true,
                    'already_joined' => false,
                    'player'         => ['id' => $userId, 'name' => $displayName, 'roomCode' => $room->code],
                    'warning'        => 'Te uniste pero hubo un error de conexión en tiempo real.',
                ])
                : redirect()->route('trivia.room.wait', ['code' => $room->code]);
        }
    }

    /** @deprecated Alias para compatibilidad — usar join() */
    public function room(Request $request, $id)
    {
        return $this->join($request, (string) $id);
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
                'joinedAt' => $p->registered_at
                    ? \Carbon\Carbon::parse($p->registered_at)->toISOString()
                    : null,
            ];
        })->values()->all();

        return response()->json([
            'status'  => $room->status,
            'players' => $players,
        ]);
    }

    /**
     * Sala de espera de una sala específica (WaitingRoom).
     * Ruta: GET /trivia/room/{code}/lobby
     */
    public function waitingRoom(string $code): Response
    {
        $roomService = app(RoomService::class);
        $user        = Auth::user();

        $room = \App\Domains\Games\Trivia\Models\TriviaRoom::with('teams')
            ->where('code', $code)
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
            ->select('user_id', 'team_id', 'status', 'registered_at')
            ->get();

        $userIds = $participants->pluck('user_id')->all();
        $names   = $userIds
            ? DB::table('users')
                ->whereIn('id', $userIds)
                ->select('id', DB::raw("TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as display_name"))
                ->get()->pluck('display_name', 'id')->all()
            : [];

        $initialPlayers = $participants->map(fn($p) => [
            'id'       => $p->user_id,
            'name'     => $names[$p->user_id] ?? 'Jugador',
            'roomCode' => $roomDto->code,
            'joinedAt' => $p->registered_at
                ? \Carbon\Carbon::parse($p->registered_at)->toISOString()
                : null,
            'teamId'   => $p->team_id,
        ])->values()->all();

        // ── Detectar si el usuario ya está inscrito ───────────────────────────
        $alreadyJoined = collect($initialPlayers)
            ->contains('id', (string) $user->id);

        // ── Resolver monedas de entrada y wallets del usuario ────────────────
        // fee_by_currency = { currency_id => amount, ... } (multidivisa)
        // Fallback: entry_currency_id / entry_fee (legado, una sola moneda)
        $entryCurrencySymbol = null;
        $userWallet          = null;
        $feeByCurrency       = $room->fee_by_currency; // array|null (cast en el modelo)

        // Recopilar todos los currency_ids relevantes para la sala
        $feeCurrencyIds = [];
        if (!empty($feeByCurrency) && is_array($feeByCurrency)) {
            $feeCurrencyIds = array_keys($feeByCurrency);
        } elseif ($room->entry_currency_id) {
            $feeCurrencyIds = [(string) $room->entry_currency_id];
        }

        // Cargar monedas y wallets del usuario en una sola pasada
        $currencies = [];
        $walletsByCurrency = [];
        if (!empty($feeCurrencyIds)) {
            $feeCurrencyIds = array_map('strval', $feeCurrencyIds);

            $currencies = \App\Models\Currency::whereIn('id', $feeCurrencyIds)
                ->get()->keyBy('id');

            $userWallets = $user->wallets()
                ->whereIn('currency_id', $feeCurrencyIds)
                ->with('currency')
                ->get();

            foreach ($userWallets as $w) {
                $walletsByCurrency[(string) $w->currency_id] = $w;
            }
        }

        // Construir entryCurrencySymbol (primera moneda o la única)
        if (!empty($feeCurrencyIds)) {
            $firstId = $feeCurrencyIds[0];
            $entryCurrencySymbol = $currencies[$firstId]?->symbol
                ?? $walletsByCurrency[$firstId]?->currency?->symbol
                ?? null;
        }

        // Construir userWallet: para multidivisa se expone la primera wallet
        // que el usuario tiene; el frontend mostrará todas cuando haya varias.
        if (!empty($feeCurrencyIds)) {
            // Intentar encontrar cualquier wallet del usuario para estas monedas
            $matchedWallet   = null;
            $matchedCurrency = null;
            foreach ($feeCurrencyIds as $cid) {
                if (isset($walletsByCurrency[$cid])) {
                    $matchedWallet   = $walletsByCurrency[$cid];
                    $matchedCurrency = $cid;
                    break;
                }
            }

            $entryCurrencySymbol = $currencies[$feeCurrencyIds[0]]?->symbol ?? $entryCurrencySymbol;

            $userWallet = [
                'balance'    => $matchedWallet ? (float) $matchedWallet->balance : 0.0,
                'symbol'     => $matchedWallet?->currency?->symbol
                                ?? $currencies[$matchedCurrency ?? $feeCurrencyIds[0]]?->symbol
                                ?? '',
                'currencyId' => $matchedCurrency ?? $feeCurrencyIds[0],
                'found'      => $matchedWallet !== null,
                // Wallets por moneda para que el frontend muestre el balance correcto
                // cuando la sala acepta múltiples monedas.
                'byCurrency' => collect($feeCurrencyIds)->mapWithKeys(function ($cid) use ($walletsByCurrency, $currencies, $feeByCurrency, $room) {
                    $w    = $walletsByCurrency[$cid] ?? null;
                    $sym  = $w?->currency?->symbol ?? $currencies[$cid]?->symbol ?? '';
                    $fee  = $feeByCurrency ? (float) ($feeByCurrency[$cid] ?? 0) : (float) ($room->entry_fee ?? 0);
                    return [$cid => [
                        'balance' => $w ? (float) $w->balance : 0.0,
                        'symbol'  => $sym,
                        'fee'     => $fee,
                        'found'   => $w !== null,
                    ]];
                })->all(),
            ];
        }

        // ── Preparar datos de equipos si aplica ────────────────────────────────
        $teams = null;
        if ($room->usesTeams() && $room->teams) {
            $teams = $room->teams->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'color' => $t->color,
                'icon' => $t->icon,
                'maxPlayers' => $t->max_players,
                'currentPlayers' => $t->current_players,
            ])->toArray();
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
                'feeByCurrency'        => $room->fee_by_currency ?: null,
                'prizeAmount'          => $room->prize_amount ? (float) $room->prize_amount : null,
                'prizeCurrencyId'      => $room->prize_currency_id,
                'prizeType'            => $room->prize_type,
                'createdAt'            => $room->created_at->toISOString(),
                'updatedAt'            => $room->updated_at->toISOString(),
            ],
            'teams' => $teams,
            'gameMode' => $room->game_mode,
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
            'status', 'game_mode', 'max_players',
            'question_count', 'time_per_question', 'created_at',
            'entry_fee', 'entry_currency_id',
            'prize_amount', 'prize_type', 'prize_currency_id'
        )->get();

        // ── Contar participantes reales desde trivia_participants ─────────────
        // current_players en trivia_rooms es denormalizado y puede estar desactualizado.
        // La fuente de verdad es trivia_participants (igual que waitingRoom).
        $roomIds = $rooms->pluck('id')->all();
        $participantCounts = [];
        if (!empty($roomIds)) {
            $participantCounts = $pgsql->table('trivia_participants')
                ->whereIn('room_id', $roomIds)
                ->whereNull('deleted_at')
                ->whereNotIn('status', ['abandoned'])
                ->selectRaw('room_id, COUNT(*) as cnt')
                ->groupBy('room_id')
                ->pluck('cnt', 'room_id')
                ->all();
        }

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

        $rooms = $rooms->map(function ($room) use ($currencySymbols, $participantCounts) {
            return array_merge((array) $room, [
                'current_players'       => (int) ($participantCounts[$room->id] ?? 0),
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
     * Ruta: GET /trivia/play/{gameId}
     *
     * La pregunta activa se obtiene de Redis (GameStateRepository) — no de BD.
     * La BD solo persiste datos finales; el estado en tiempo real vive en Redis.
     */
    public function play(string $gameId): Response
    {
        $user  = Auth::user();
        $pgsql = DB::connection('pgsql_trivia');

        $game = $pgsql->table('trivia_games')
            ->where('id', $gameId)
            ->whereIn('status', ['playing', 'active'])
            ->first();

        if (! $game) {
            abort(404, 'Juego no encontrado');
        }

        $room = $pgsql->table('trivia_rooms')
            ->where('id', $game->room_id)
            ->first();

        // ── Pote: prize_amount fijo o calculado desde entradas ────────────────
        $potAmount = $room->prize_amount > 0
            ? (float) $room->prize_amount
            : (float) (($room->entry_fee ?? 0) * ($game->player_count ?? 0));

        // ── Pregunta activa desde Redis ───────────────────────────────────────
        $stateRepo    = app(GameStateRepositoryInterface::class);
        $questionData = $stateRepo->getCurrentQuestion($gameId);
        $gameState    = $stateRepo->getState($gameId);

        $question = null;
        if ($questionData) {
            $options = $questionData['options'] ?? [];
            if (is_string($options)) {
                $options = json_decode($options, true) ?? [];
            }
            $question = [
                'id'        => (string) ($questionData['question_id'] ?? $questionData['id'] ?? ''),
                'text'      => $questionData['question_text'] ?? $questionData['text'] ?? '',
                'image'     => $questionData['image_url'] ?? null,
                'options'   => $options,
                'timeLimit' => (int) ($questionData['time_limit_seconds'] ?? $room->time_per_question ?? 20),
                'category'  => $questionData['category'] ?? null,
            ];
        }

        $currentQuestionIndex = $gameState?->currentQuestionIndex ?? ($game->current_question_index ?? 0);
        $totalQuestions       = $gameState?->totalQuestions ?? ($room->question_count ?? 10);

        return Inertia::render('Games/Trivia/Play', [
            'room' => [
                'id'             => (string) $room->id,
                'code'           => $room->code,
                'title'          => $room->title,
                'potAmount'      => $potAmount,
                'currentPlayers' => $game->player_count ?? 0,
                'maxPlayers'     => $room->max_players ?? 100,
            ],
            'game' => [
                'id'              => $gameId,
                'currentQuestion' => $currentQuestionIndex,
                'totalQuestions'  => $totalQuestions,
            ],
            'question' => $question,
            'player'   => [
                'id'     => (string) $user->id,
                'name'   => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->username ?? 'Jugador'),
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

        // Leer premio ganado desde trivia_participants (actualizado por PrizeDistributionService)
        $participantData = $pgsql->table('trivia_participants')
            ->where('room_id', $game->room_id)
            ->where('user_id', (string) $user->id)
            ->first();

        $wonPrize = $participantData && $participantData->prize_won > 0
            ? (float) $participantData->prize_won
            : null;

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
