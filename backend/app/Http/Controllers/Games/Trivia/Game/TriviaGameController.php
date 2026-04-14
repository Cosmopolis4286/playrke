<?php

namespace App\Http\Controllers\Games\Trivia\Game;

use App\Http\Controllers\Controller;
use App\Traits\Users\UserTrait;
use App\Domains\Games\Trivia\Services\RoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

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
        $user = Auth::user();

        $room = \App\Domains\Games\Trivia\Models\TriviaRoom::findOrFail($id);

        if (!in_array($room->status, ['waiting', 'playing'])) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'La sala ya no está disponible'], 422);
            }
            return back()->with('error', 'La sala ya no está disponible.');
        }

        // Registrar participante si no existe
        $exists = DB::connection('pgsql_trivia')
            ->table('trivia_participants')
            ->where('room_id', $room->id)
            ->where('user_id', (string) $user->id)
            ->whereNull('deleted_at')
            ->exists();

        if (!$exists) {
            DB::connection('pgsql_trivia')->table('trivia_participants')->insert([
                'id'            => (string) \Illuminate\Support\Str::ulid(),
                'room_id'       => (string) $room->id,
                'user_id'       => (string) $user->id,
                'status'        => 'registered',
                'registered_at' => now(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'player'  => [
                    'id'       => (string) $user->id,
                    'name'     => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->username ?? 'Jugador'),
                    'roomCode' => $room->code,
                ],
            ]);
        }

        return redirect()->route('trivia.room.wait', ['code' => $room->code]);
    }

    /**
     * Display the in-game interface. (Legacy — sistema deprecated)
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

        $room = \App\Domains\Games\Trivia\Models\TriviaRoom::where('code', $code)
            ->whereIn('status', ['waiting', 'playing'])
            ->firstOrFail();

        $roomDto  = $roomService->getRoom((string) $room->id);
        $players  = $roomService->listPlayers((string) $room->id);

        return Inertia::render('Trivia/WaitingRoom', [
            'room' => [
                'id'               => (string) $roomDto->roomId,
                'code'             => $roomDto->code,
                'title'            => $roomDto->title,
                'hostId'           => $roomDto->hostId,
                'status'           => $roomDto->status,
                'maxPlayers'       => $roomDto->maxPlayers,
                'currentPlayers'   => $roomDto->currentPlayers,
                'questionCount'    => $room->question_count,
                'timePerQuestion'  => $room->time_per_question,
                'createdAt'        => $room->created_at->toISOString(),
                'updatedAt'        => $room->updated_at->toISOString(),
            ],
            'initialPlayers' => array_map(fn($p) => [
                'id'       => $p->playerId,
                'name'     => $p->nickname,
                'roomCode' => $roomDto->code,
            ], $players),
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

        return Inertia::render('Trivia/Lobby', [
            'rooms'      => $rooms,
            'categories' => $categories,
            'stats'      => $stats,
            'filters'    => compact('search', 'category'),
        ]);
    }
}
