<?php

declare(strict_types=1);

namespace App\Http\Controllers\Games\Trivia\Room;

use App\Domains\Games\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

        // Enriquecer con datos de Redis (jugadores actuales)
        $rooms->getCollection()->transform(function ($room) {
            try {
                $roomDto = $this->roomService->getRoom((string) $room->id);
                $room->current_players = $roomDto->currentPlayers;
                $room->player_ids = $roomDto->playerIds;
            } catch (\Exception) {
                $room->current_players = 0;
                $room->player_ids = [];
            }
            return $room;
        });

        // Stats para el dashboard
        $stats = [
            'total' => TriviaRoom::count(),
            'waiting' => TriviaRoom::where('status', 'waiting')->count(),
            'playing' => TriviaRoom::where('status', 'playing')->count(),
            'closed' => TriviaRoom::where('status', 'closed')->count(),
        ];

        return Inertia::render('Trivia/Admin/RoomsIndex', [
            'rooms' => $rooms,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
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
     * Formulario para crear sala.
     */
    public function create(): Response
    {
        return Inertia::render('Trivia/Admin/RoomsCreate', [
            'defaultSettings' => [
                'max_players' => 100,
                'question_count' => 10,
                'time_per_question' => 15,
            ],
            'categories' => $this->availableCategories(),
        ]);
    }

    /**
     * Guardar nueva sala.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:50',
            'max_players' => 'required|integer|min:2|max:500',
            'question_count' => 'required|integer|min:1|max:100',
            'time_per_question' => 'required|integer|min:5|max:300',
            'settings' => 'nullable|array',
        ]);

        try {
            $roomDto = $this->roomService->createRoom([
                'host_id' => (string) auth()->id(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'] ?? null,
                'max_players' => $validated['max_players'],
                'question_count' => $validated['question_count'],
                'time_per_question' => $validated['time_per_question'],
                'settings' => $validated['settings'] ?? [],
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
        $room = TriviaRoom::findOrFail($id);

        try {
            $roomDto = $this->roomService->getRoom($id);
            $players = $this->roomService->listPlayers($id);
        } catch (\Exception) {
            $roomDto = null;
            $players = [];
        }

        return Inertia::render('Trivia/Admin/RoomsShow', [
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
        $room = TriviaRoom::findOrFail($id);

        // Solo permitir editar si está en espera
        if ($room->status !== 'waiting') {
            return redirect()
                ->route('trivia-rooms-admin.index')
                ->with('error', 'No se puede editar una sala que ya inició o cerró.');
        }

        return Inertia::render('Trivia/Admin/RoomsEdit', [
            'room' => $room,
            'categories' => $this->availableCategories(),
        ]);
    }

    /**
     * Actualizar sala.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:50',
            'max_players' => 'required|integer|min:2|max:500',
            'settings' => 'nullable|array',
        ]);

        try {
            $this->roomService->updateRoomConfig($id, [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'] ?? null,
                'max_players' => $validated['max_players'],
                'settings' => $validated['settings'] ?? [],
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
