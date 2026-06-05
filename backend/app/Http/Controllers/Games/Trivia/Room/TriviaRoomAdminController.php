<?php

declare(strict_types=1);

namespace App\Http\Controllers\Games\Trivia\Room;

use App\Domains\Games\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Http\Controllers\Controller;
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
        private readonly RoomServiceInterface $roomService
    ) {}

    /**
     * Listado de salas de trivia.
     */
    public function index(Request $request): Response
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
        $roomIds = $rooms->pluck('id')->map(fn($id) => (string) $id)->all();
        $participantCounts = [];
        if (!empty($roomIds)) {
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

        $rooms->getCollection()->transform(function ($room) use ($participantCounts) {
            $room->current_players = (int) ($participantCounts[(string) $room->id] ?? 0);
            return $room;
        });

        // Stats para el dashboard
        $stats = [
            'total' => TriviaRoom::count(),
            'waiting' => TriviaRoom::where('status', 'waiting')->count(),
            'playing' => TriviaRoom::where('status', 'playing')->count(),
            'closed' => TriviaRoom::where('status', 'closed')->count(),
            'by_mode' => [
                'free_for_all' => TriviaRoom::where('game_mode', 'free_for_all')->count(),
                'teams' => TriviaRoom::where('game_mode', 'teams')->count(),
                'duel' => TriviaRoom::where('game_mode', 'duel')->count(),
            ],
        ];

        return Inertia::render('Games/Trivia/Admin/RoomsIndex', [
            'rooms' => $rooms,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Monedas activas disponibles para seleccionar como fee/prize.
     */
    private function availableCurrencies(): array
    {
        return DB::table('currencies')
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->select('id', 'symbol', 'name', 'iso_code')
            ->get()
            ->map(fn($c) => [
                'id'      => (string) $c->id,
                'symbol'  => $c->symbol,
                'name'    => $c->name,
                'iso_code'=> $c->iso_code,
            ])
            ->values()
            ->all();
    }

    /**
     * Categorías disponibles (unión de rooms + questions para sugerencias).
     */
    private function availableCategories(): array
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
     * Modos de juego disponibles.
     */
    private function availableGameModes(): array
    {
        return [
            ['value' => 'free_for_all', 'label' => 'Todos vs Todos', 'description' => 'Cada jugador compite individualmente'],
            ['value' => 'teams', 'label' => 'Equipos', 'description' => 'Jugadores divididos en equipos, gana el equipo con más puntos'],
            ['value' => 'duel', 'label' => 'Duelo / Versus', 'description' => '2 equipos o 1vsX. Gana quien tenga más respuestas correctas'],
        ];
    }

    /**
     * Formulario para crear sala.
     */
    public function create(): Response
    {
        return Inertia::render('Games/Trivia/Admin/RoomsCreate', [
            'defaultSettings' => [
                'max_players' => 100,
                'question_count' => 10,
                'time_per_question' => 15,
            ],
            'gameModes'   => $this->availableGameModes(),
            'categories'  => $this->availableCategories(),
            'currencies'  => $this->availableCurrencies(),
        ]);
    }

    /**
     * Guardar nueva sala.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string|max:1000',
            'category'           => 'nullable|string|max:50',
            'game_mode'          => 'required|in:free_for_all,teams,duel,tournament',
            'teams_config'       => 'nullable|array',
            'teams_config.team_count' => 'nullable|integer|min:2|max:10',
            'teams_config.team_size'  => 'nullable|integer|min:1|max:100',
            'teams_config.duel_type'  => 'nullable|in:2vs2,1vsX',
            'max_players'        => 'required|integer|min:2|max:500',
            'question_count'     => 'required|integer|min:1|max:100',
            'time_per_question'  => 'required|integer|min:5|max:300',
            'entry_fee'              => 'nullable|numeric|min:0',
            'entry_currency_id'      => 'nullable|string',
            'fee_by_currency'        => 'nullable|array',
            'fee_by_currency.*'      => 'nullable|numeric|min:0',
            'prize_type'             => 'nullable|in:fixed,accumulated',
            'prize_amount'           => 'nullable|numeric|min:0',
            'prize_currency_id'      => 'nullable|string',
            'scheduled_at'           => 'nullable|date',
            'settings'               => 'nullable|array',
        ]);

        // Validar que scheduled_at sea futuro (con tolerancia de 1 minuto para diferencias de timezone)
        if (!empty($validated['scheduled_at'])) {
            $scheduledTime = new \DateTime($validated['scheduled_at']);
            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            if ($scheduledTime <= $now->modify('-1 minute')) {
                return redirect()
                    ->back()
                    ->with('error', 'La fecha programada debe ser futura.')
                    ->withInput();
            }
        }

        // Normalizar fee_by_currency: eliminar entradas vacías/nulas
        $feeByCurrency = collect($validated['fee_by_currency'] ?? [])
            ->filter(fn($v) => $v !== null && $v !== '' && (float) $v > 0)
            ->map(fn($v) => (float) $v)
            ->all();

        // Si sólo hay una moneda en fee_by_currency, sincronizar entry_fee/entry_currency_id
        if (count($feeByCurrency) === 1) {
            $validated['entry_currency_id'] = array_key_first($feeByCurrency);
            $validated['entry_fee']         = array_values($feeByCurrency)[0];
        }

        try {
            $roomDto = $this->roomService->createRoom([
                'host_id'           => (string) auth()->id(),
                'title'             => $validated['title'],
                'description'       => $validated['description'] ?? null,
                'category'          => $validated['category'] ?? null,
                'game_mode'         => $validated['game_mode'],
                'teams_config'      => $validated['teams_config'] ?? null,
                'max_players'       => $validated['max_players'],
                'question_count'    => $validated['question_count'],
                'time_per_question' => $validated['time_per_question'],
                'entry_fee'         => $validated['entry_fee'] ?? 0,
                'entry_currency_id' => $validated['entry_currency_id'] ?? null,
                'fee_by_currency'   => empty($feeByCurrency) ? null : $feeByCurrency,
                'prize_type'        => $validated['prize_type'] ?? 'accumulated',
                'prize_amount'      => $validated['prize_amount'] ?? 0,
                'prize_currency_id' => $validated['prize_currency_id'] ?? null,
                'scheduled_at'      => $validated['scheduled_at'] ?? null,
                'settings'          => $validated['settings'] ?? [],
            ]);

            return redirect()
                ->route('trivia-rooms-admin.index')
                ->with('success', 'Sala creada exitosamente. Código: ' . $roomDto->code);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al crear sala: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Ver detalle de sala (con jugadores en tiempo real).
     */
    public function show(string $id): Response
    {
        $room = TriviaRoom::with('teams')->findOrFail($id);

        try {
            $roomDto = $this->roomService->getRoom($id);
            $players = $this->roomService->listPlayers($id);
        } catch (\Exception) {
            $roomDto = null;
            $players = [];
        }

        return Inertia::render('Games/Trivia/Admin/RoomsShow', [
            'room' => $room,
            'roomState' => $roomDto?->toArray(),
            'players' => array_map(fn($p) => $p->toArray(), $players),
            'playerCount' => count($players),
        ]);
    }

    /**
     * Formulario para editar sala.
     */
    public function edit(string $id): Response
    {
        $room = TriviaRoom::with('teams')->findOrFail($id);

        // Solo permitir editar si está en espera
        if ($room->status !== 'waiting') {
            return redirect()
                ->route('trivia-rooms-admin.index')
                ->with('error', 'No se puede editar una sala que ya inició o cerró.');
        }

        return Inertia::render('Games/Trivia/Admin/RoomsEdit', [
            'room'       => $room,
            'gameModes'  => $this->availableGameModes(),
            'categories' => $this->availableCategories(),
            'currencies' => $this->availableCurrencies(),
        ]);
    }

    /**
     * Actualizar sala.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string|max:1000',
            'category'           => 'nullable|string|max:50',
            'max_players'        => 'required|integer|min:2|max:500',
            'entry_fee'          => 'nullable|numeric|min:0',
            'entry_currency_id'  => 'nullable|string',
            'fee_by_currency'    => 'nullable|array',
            'fee_by_currency.*'  => 'nullable|numeric|min:0',
            'prize_type'         => 'nullable|in:fixed,accumulated',
            'prize_amount'       => 'nullable|numeric|min:0',
            'prize_currency_id'  => 'nullable|string',
            'scheduled_at'       => 'nullable|date',
            'settings'           => 'nullable|array',
        ]);

        // Validar que scheduled_at sea futuro si se está actualizando (con tolerancia de 1 minuto)
        if (!empty($validated['scheduled_at'])) {
            $scheduledTime = new \DateTime($validated['scheduled_at']);
            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            if ($scheduledTime <= $now->modify('-1 minute')) {
                return redirect()
                    ->back()
                    ->with('error', 'La fecha programada debe ser futura.')
                    ->withInput();
            }
        }

        // Normalizar fee_by_currency: eliminar entradas vacías/nulas
        $feeByCurrency = collect($validated['fee_by_currency'] ?? [])
            ->filter(fn($v) => $v !== null && $v !== '' && (float) $v > 0)
            ->map(fn($v) => (float) $v)
            ->all();

        // Si sólo hay una moneda, sincronizar entry_fee/entry_currency_id para compatibilidad
        if (count($feeByCurrency) === 1) {
            $validated['entry_currency_id'] = array_key_first($feeByCurrency);
            $validated['entry_fee']         = array_values($feeByCurrency)[0];
        }

        try {
            $this->roomService->updateRoomConfig($id, [
                'title'             => $validated['title'],
                'description'       => $validated['description'] ?? null,
                'category'          => $validated['category'] ?? null,
                'max_players'       => $validated['max_players'],
                'entry_fee'         => $validated['entry_fee'] ?? 0,
                'entry_currency_id' => $validated['entry_currency_id'] ?? null,
                'fee_by_currency'   => empty($feeByCurrency) ? null : $feeByCurrency,
                'prize_type'        => $validated['prize_type'] ?? 'accumulated',
                'prize_amount'      => $validated['prize_amount'] ?? 0,
                'prize_currency_id' => $validated['prize_currency_id'] ?? null,
                'scheduled_at'      => $validated['scheduled_at'] ?? null,
                'settings'          => $validated['settings'] ?? [],
            ]);

            return redirect()
                ->route('trivia-rooms-admin.index')
                ->with('success', 'Sala actualizada exitosamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al actualizar sala: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Eliminar/Cerrar sala.
     */
    public function destroy(string $id)
    {
        try {
            $this->roomService->closeRoom($id, 'deleted_by_admin');

            return redirect()
                ->route('trivia-rooms-admin.index')
                ->with('success', 'Sala cerrada exitosamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al cerrar sala: ' . $e->getMessage());
        }
    }

    /**
     * Forzar cierre de sala (para salas playing o waiting).
     */
    public function forceClose(string $id)
    {
        try {
            $room = TriviaRoom::findOrFail($id);
            $room->update(['status' => 'closed', 'ended_at' => now()]);

            // Limpiar Redis
            $this->roomService->closeRoom($id, 'force_closed_by_admin');

            return redirect()
                ->route('trivia-rooms-admin.index')
                ->with('success', 'Sala forzada a cerrar exitosamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al cerrar sala: ' . $e->getMessage());
        }
    }
}
