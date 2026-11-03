<?php

declare(strict_types=1);

namespace App\Http\Controllers\Games\Trivia\Room;

use App\Contracts\Games\Common\GameMode\GameModeRepositoryInterface;
use App\Domains\Games\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Services\TriviaRoomRefundService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trivia\UpdateRoomRequest;
use App\Models\Game;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador Admin: Gestión de Salas de Trivia (Domain-Driven).
 *
 * Usa el nuevo sistema TriviaRoom con PostgreSQL + Redis.
 * NO usa el legacy TriviaMulti.
 */
final class TriviaRoomAdminController extends Controller
{
    public function __construct(
        private readonly RoomServiceInterface $roomService,
        private readonly GameModeRepositoryInterface $gameModes,
        private readonly TriviaRoomRefundService $refundService,
    ) {
    }

    /**
     * Formulario para crear sala.
     */
    public function create() : Response
    {
        $this->authorize('create', TriviaRoom::class);

        return Inertia::render('Games/Trivia/Admin/RoomsCreate', [
            'defaultSettings' => [
                'max_players' => 100,
                'question_count' => 10,
                'time_per_question' => 15,
            ],
            'gameModes' => $this->availableGameModes(),
            'categories' => $this->availableCategories(),
            'currencies' => $this->availableCurrencies(),
        ]);
    }

    /**
     * Eliminar/Cerrar sala.
     *
     * Reembolsa el entry fee de cada participante confirmado antes de
     * cerrar: sin esto, un admin podía cerrar una sala con jugadores que ya
     * pagaron su entrada y ese dinero quedaba cobrado sin partida jugada y
     * sin ningún mecanismo de recuperación (ver TriviaRoomRefundService).
     */
    public function destroy(string $id)
    {
        // findOrFail()/authorize() fuera del try: AuthorizationException extends
        // Exception, así que dentro del try el catch(\Exception) genérico de abajo
        // la atrapaba y la convertía en un redirect con mensaje de error en vez de
        // dejar pasar la respuesta 403 real de Laravel.
        $room = TriviaRoom::findOrFail($id);
        $this->authorize('delete', $room);

        if ($room->status === 'playing') {
            return redirect()
                ->back()
                ->with('error', 'No se puede cerrar una sala mientras la partida está en curso. Espera a que finalice.');
        }

        try {
            $refunded = $this->refundService->refundConfirmedParticipants(
                $room,
                "La sala '{$room->title}' ({$room->code}) fue cerrada por un administrador. Se te reembolsó tu entrada.",
            );

            $this->roomService->closeRoom($id, 'deleted_by_admin');

            $message = $refunded > 0
                ? "Sala cerrada exitosamente. Se reembolsaron {$refunded} entrada(s)."
                : 'Sala cerrada exitosamente.';

            return redirect()
                ->route('trivia-rooms-admin.index')
                ->with('success', $message);
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al cerrar sala: ' . $e->getMessage());
        }
    }

    /**
     * Formulario para editar sala.
     */
    public function edit(string $id) : Response
    {
        $room = TriviaRoom::with('teams')->findOrFail($id);
        $this->authorize('update', $room);

        // Solo permitir editar si está en espera
        if ($room->status !== 'waiting') {
            return redirect()
                ->route('trivia-rooms-admin.index')
                ->with('error', 'No se puede editar una sala que ya inició o cerró.');
        }

        return Inertia::render('Games/Trivia/Admin/RoomsEdit', [
            'room' => $room,
            'gameModes' => $this->availableGameModes(),
            'categories' => $this->availableCategories(),
            'currencies' => $this->availableCurrencies(),
        ]);
    }

    /**
     * Forzar cierre de sala (para salas playing o waiting).
     *
     * Reembolsa el entry fee de cada participante confirmado antes de
     * cerrar — ver docblock de destroy() para el porqué.
     */
    public function forceClose(string $id)
    {
        // Mismo motivo que en destroy(): fuera del try para que un 403 real
        // no sea absorbido por el catch(\Exception) genérico de abajo.
        $room = TriviaRoom::findOrFail($id);
        $this->authorize('forceClose', $room);

        if ($room->status === 'playing') {
            return redirect()
                ->back()
                ->with('error', 'No se puede forzar el cierre mientras la partida está en curso. Espera a que finalice.');
        }

        try {
            $refunded = $this->refundService->refundConfirmedParticipants(
                $room,
                "La sala '{$room->title}' ({$room->code}) fue cerrada por un administrador. Se te reembolsó tu entrada.",
            );

            $room->update(['status' => 'closed', 'ended_at' => now()]);

            // Limpiar Redis
            $this->roomService->closeRoom($id, 'force_closed_by_admin');

            $message = $refunded > 0
                ? "Sala forzada a cerrar exitosamente. Se reembolsaron {$refunded} entrada(s)."
                : 'Sala forzada a cerrar exitosamente.';

            return redirect()
                ->route('trivia-rooms-admin.index')
                ->with('success', $message);
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al cerrar sala: ' . $e->getMessage());
        }
    }

    /**
     * Listado de salas de trivia.
     */
    public function index(Request $request) : Response
    {
        $query = TriviaRoom::query()
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search !== '') {
            $query->where('title', 'ilike', '%' . $request->search . '%');
        }

        $rooms = $query->paginate(15)->withQueryString();

        // Contar jugadores reales desde trivia_participants (fuente de verdad)
        // Redis puede estar desactualizado — mismo patrón que lobby y waitingRoom
        $roomIds = $rooms->pluck('id')->map(fn ($id) => (string) $id)->all();
        $participantCounts = [];
        if (! empty($roomIds)) {
            $participantCounts = DB::connection('pgsql_trivia')
                ->table('trivia_participants')
                ->whereIn('room_id', $roomIds)
                ->whereNull('deleted_at')
                ->whereNotIn('status', ['abandoned'])
                ->selectRaw('room_id, COUNT(*) as cnt')
                ->groupBy('room_id')
                ->pluck('cnt', 'room_id')
                ->all();
        }

        $rooms->getCollection()->transform(function($room) use ($participantCounts) {
            $room->current_players = (int) ($participantCounts[(string) $room->id] ?? 0);

            return $room;
        });

        // Stats para el dashboard
        $stats = [
            'total' => TriviaRoom::count(),
            'waiting' => TriviaRoom::where('status', 'waiting')->count(),
            'playing' => TriviaRoom::where('status', 'playing')->count(),
            'closed' => TriviaRoom::where('status', 'closed')->count(),
            'by_mode' => TriviaRoom::query()
                ->whereNotNull('game_mode_id')
                ->selectRaw('game_mode_id, COUNT(*) as cnt')
                ->groupBy('game_mode_id')
                ->pluck('cnt', 'game_mode_id')
                ->all(),
        ];

        return Inertia::render('Games/Trivia/Admin/RoomsIndex', [
            'rooms' => $rooms,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Ver detalle de sala (con jugadores en tiempo real).
     */
    public function show(string $id) : Response
    {
        $room = TriviaRoom::with('teams')->findOrFail($id);

        try {
            $roomDto = $this->roomService->getRoom($id);
            $players = $this->roomService->listPlayers($id);
        } catch (Exception) {
            $roomDto = null;
            $players = [];
        }

        return Inertia::render('Games/Trivia/Admin/RoomsShow', [
            'room' => $room,
            'roomState' => $roomDto?->toArray(),
            'players' => array_map(fn ($p) => $p->toArray(), $players),
            'playerCount' => count($players),
            'winners' => $this->roomWinners($id),
        ]);
    }

    /**
     * Guardar nueva sala.
     */
    public function store(Request $request)
    {
        $this->authorize('create', TriviaRoom::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:50',
            'game_mode' => ['required', \Illuminate\Validation\Rule::exists('mysql.game_modes', 'id')],
            'teams_config' => 'nullable|array',
            'teams_config.team_count' => 'nullable|integer|min:2|max:10',
            'teams_config.team_size' => 'nullable|integer|min:1|max:100',
            'teams_config.duel_type' => 'nullable|in:2vs2,1vsX',
            'max_players' => 'required|integer|min:2|max:500',
            'question_count' => 'required|integer|min:1|max:100',
            'time_per_question' => 'required|integer|min:5|max:300',
            'entry_fee' => 'nullable|numeric|min:0',
            'entry_currency_id' => 'nullable|string',
            'fee_by_currency' => 'nullable|array',
            'fee_by_currency.*' => 'nullable|numeric|min:0',
            'prize_type' => 'nullable|in:fixed,accumulated',
            'prize_amount' => 'nullable|numeric|min:0',
            'prize_currency_id' => 'nullable|string',
            'prize_model' => 'nullable|in:guaranteed,pool',
            'sponsor_prize' => 'nullable|numeric|min:0',
            'platform_rake_percent' => 'nullable|numeric|min:0|max:30',
            'prize_payouts' => 'nullable|array',
            'prize_payouts.*.rank' => 'required_with:prize_payouts|integer|min:1',
            'prize_payouts.*.percent' => 'required_with:prize_payouts|numeric|min:0|max:1',
            'scheduled_at' => 'nullable|date',
            'settings' => 'nullable|array',
        ]);

        // Validar que scheduled_at sea futuro (con tolerancia de 1 minuto para diferencias de timezone)
        if (! empty($validated['scheduled_at'])) {
            $scheduledTime = new DateTime($validated['scheduled_at']);
            $now = new DateTime('now', new DateTimeZone('UTC'));
            if ($scheduledTime <= $now->modify('-1 minute')) {
                return redirect()
                    ->back()
                    ->with('error', 'La fecha programada debe ser futura.')
                    ->withInput();
            }
        }

        // Normalizar fee_by_currency: eliminar entradas vacías/nulas
        $feeByCurrency = collect($validated['fee_by_currency'] ?? [])
            ->filter(fn ($v) => $v !== null && $v !== '' && (float) $v > 0)
            ->map(fn ($v) => (float) $v)
            ->all();

        // Si sólo hay una moneda en fee_by_currency, sincronizar entry_fee/entry_currency_id
        if (count($feeByCurrency) === 1) {
            $validated['entry_currency_id'] = array_key_first($feeByCurrency);
            $validated['entry_fee'] = array_values($feeByCurrency)[0];
        }

        try {
            $roomDto = $this->roomService->createRoom([
                'host_id' => (string) auth()->id(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'] ?? null,
                'game_mode_id' => $validated['game_mode'],
                'teams_config' => $validated['teams_config'] ?? null,
                'max_players' => $validated['max_players'],
                'question_count' => $validated['question_count'],
                'time_per_question' => $validated['time_per_question'],
                'entry_fee' => $validated['entry_fee'] ?? 0,
                'entry_currency_id' => $validated['entry_currency_id'] ?? null,
                'fee_by_currency' => empty($feeByCurrency) ? null : $feeByCurrency,
                'prize_type' => $validated['prize_type'] ?? 'accumulated',
                'prize_amount' => $validated['prize_amount'] ?? 0,
                'prize_currency_id' => $validated['prize_currency_id'] ?? null,
                'prize_model' => $validated['prize_model'] ?? 'guaranteed',
                'prize_payouts' => $validated['prize_payouts'] ?? null,
                'platform_rake_percent' => $validated['platform_rake_percent'] ?? 0,
                'sponsor_prize' => $validated['sponsor_prize'] ?? 0,
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'settings' => $validated['settings'] ?? [],
            ]);

            return redirect()
                ->route('trivia-rooms-admin.index')
                ->with('success', 'Sala creada exitosamente. Código: ' . $roomDto->code);
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al crear sala: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Actualizar sala.
     */
    public function update(UpdateRoomRequest $request, string $id)
    {
        $room = TriviaRoom::findOrFail($id);
        $this->authorize('update', $room);

        if ($room->status !== 'waiting') {
            return redirect()
                ->route('trivia-rooms-admin.index')
                ->with('error', 'No se puede editar una sala que ya inició o cerró.');
        }

        $validated = $request->validated();

        // Normalizar fee_by_currency: eliminar entradas vacías/nulas/cero
        $feeByCurrency = collect($validated['fee_by_currency'] ?? [])
            ->filter(fn ($v) => $v !== null && $v !== '' && (float) $v > 0)
            ->map(fn ($v) => (float) $v)
            ->all();

        // Si sólo hay una moneda, sincronizar entry_fee/entry_currency_id para compatibilidad
        if (count($feeByCurrency) === 1) {
            $validated['entry_currency_id'] = array_key_first($feeByCurrency);
            $validated['entry_fee'] = array_values($feeByCurrency)[0];
        } elseif (empty($feeByCurrency)) {
            $validated['entry_currency_id'] = null;
            $validated['entry_fee'] = 0;
        }

        try {
            $this->roomService->updateRoomConfig($id, [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'] ?? null,
                'max_players' => $validated['max_players'],
                'entry_fee' => $validated['entry_fee'] ?? 0,
                'entry_currency_id' => $validated['entry_currency_id'] ?? null,
                'fee_by_currency' => empty($feeByCurrency) ? null : $feeByCurrency,
                'prize_type' => $validated['prize_type'] ?? 'accumulated',
                'prize_amount' => $validated['prize_amount'] ?? 0,
                'prize_currency_id' => $validated['prize_currency_id'] ?? null,
                'prize_model' => $validated['prize_model'] ?? 'guaranteed',
                'prize_payouts' => $validated['prize_payouts'] ?? null,
                'platform_rake_percent' => $validated['platform_rake_percent'] ?? 0,
                'sponsor_prize' => $validated['sponsor_prize'] ?? 0,
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'settings' => $validated['settings'] ?? [],
            ]);

            return redirect()
                ->route('trivia-rooms-admin.index')
                ->with('success', 'Sala actualizada exitosamente.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al actualizar sala: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Categorías disponibles (unión de rooms + questions para sugerencias).
     */
    private function availableCategories() : array
    {
        $fromRooms = TriviaRoom::whereNotNull('category')
            ->distinct()
            ->pluck('category');

        $fromQuestions = \Illuminate\Support\Facades\DB::connection('pgsql_trivia')
            ->table('trivia_questions')
            ->whereNull('deleted_at')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return $fromRooms->merge($fromQuestions)->unique()->sort()->values()->all();
    }

    /**
     * Monedas activas disponibles para seleccionar como fee/prize.
     */
    private function availableCurrencies() : array
    {
        return DB::table('currencies')
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->select('id', 'symbol', 'name', 'iso_code')
            ->get()
            ->map(fn ($c) => [
                'id' => (string) $c->id,
                'symbol' => $c->symbol,
                'name' => $c->name,
                'iso_code' => $c->iso_code,
            ])
            ->values()
            ->all();
    }

    /**
     * Modos de juego disponibles para Trivia, desde el catálogo dinámico
     * game_modes (administrable por admins vía GameModeController).
     */
    private function availableGameModes() : array
    {
        $triviaGameId = Game::where('name', 'TRIVIA')->value('id');
        if (! $triviaGameId) {
            return [];
        }

        return $this->gameModes->getActiveByGame($triviaGameId)
            ->map(fn ($mode) => [
                'value' => (string) $mode->id,
                'label' => $mode->label,
            ])
            ->values()
            ->all();
    }

    /**
     * Ganadores de la sala (final_rank asignado) con su estado de premio —
     * fuente: trivia_participants (Postgres, persiste al finalizar la
     * partida), no Redis. Incluye nombre desde MySQL users. Usado por la UI
     * de disputa de premios (TriviaPrizeDisputeController).
     */
    private function roomWinners(string $roomId) : array
    {
        $winners = DB::connection('pgsql_trivia')
            ->table('trivia_participants')
            ->where('room_id', $roomId)
            ->whereNotNull('final_rank')
            ->orderBy('final_rank')
            ->select('id', 'user_id', 'final_rank', 'prize_won', 'prize_status')
            ->get();

        if ($winners->isEmpty()) {
            return [];
        }

        $userIds = $winners->pluck('user_id')->unique()->all();
        $names = DB::table('users')
            ->whereIn('id', $userIds)
            ->select('id', DB::raw("TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as display_name"))
            ->get()
            ->pluck('display_name', 'id');

        return $winners->map(fn ($w) => [
            'id' => $w->id,
            'userId' => (string) $w->user_id,
            'name' => $names[$w->user_id] ?? 'Jugador',
            'finalRank' => (int) $w->final_rank,
            'prizeWon' => (float) $w->prize_won,
            'prizeStatus' => $w->prize_status,
        ])->values()->all();
    }
}
