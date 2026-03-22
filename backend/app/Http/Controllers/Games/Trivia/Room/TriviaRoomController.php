<?php

namespace App\Http\Controllers\Games\Trivia\Room;

use App\Http\Controllers\Controller;
use App\Models\Games\Trivia\TriviaMulti;
use App\Models\Coin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración de Salas de Trivia.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP para salas de trivia
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class TriviaRoomController extends Controller
{
    /**
     * Display a listing of trivia rooms.
     */
    public function index(): Response
    {
        $rooms = TriviaMulti::with(['Coin', 'Creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Modules/Admin/Trivia/RoomsIndex', [
            'title' => 'Salas de Trivia',
            'rooms' => $rooms
        ]);
    }

    /**
     * Show the form for creating a new trivia room.
     */
    public function create(): Response
    {
        $coins = Coin::where('status', 1)->get();

        return Inertia::render('Modules/Admin/Trivia/RoomsCreate', [
            'title' => 'Crear Sala de Trivia',
            'coins' => $coins
        ]);
    }

    /**
     * Store a newly created trivia room in storage.
     */
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'coin' => 'required|exists:coins,name',
            'players' => 'required|integer|min:2|max:100',
            'prize' => 'required|numeric|min:0',
            'questions_count' => 'required|integer|min:1|max:50',
            'time_per_question' => 'required|integer|min:10|max:300',
        ]);

        if ($validated->fails()) {
            return redirect()->back()
                ->withErrors($validated)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $coin = Coin::where('name', $request->coin)->first();

            // Crear la sala de trivia
            $room = TriviaMulti::create([
                'name' => $request->name,
                'coin' => $coin->id,
                'players' => $request->players,
                'prize' => $request->prize,
                'questions_count' => $request->questions_count,
                'time_per_question' => $request->time_per_question,
                'status' => 1, // Activa
                'fecha' => now()->format('Y-m-d'),
                'hora' => now()->format('H:i:s'),
                'created_by' => auth()->user()->id,
            ]);

            DB::commit();

            Session::flash('message', 'Sala de trivia creada correctamente.');
            Session::flash('type', 'success');
            return redirect()->route('trivia-rooms.index');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al crear sala de trivia.', [
                'error' => $e->getMessage(),
                'usuario' => Auth::id() ?? 'Invitado',
            ]);

            Session::flash('error', 'Hubo un error al crear la sala de trivia. Inténtelo de nuevo.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified trivia room.
     */
    public function show($id): Response
    {
        $room = TriviaMulti::with(['Coin', 'Creator', 'Questions'])
            ->findOrFail($id);

        $players = DB::table('triv_multi_user')
            ->join('users', 'users.id', 'triv_multi_user.user')
            ->where('triv_multi_user.room', $id)
            ->select('users.name', 'users.email', 'triv_multi_user.*')
            ->get();

        return Inertia::render('Modules/Admin/Trivia/RoomsShow', [
            'title' => 'Detalles de Sala de Trivia',
            'room' => $room,
            'players' => $players
        ]);
    }

    /**
     * Show the form for editing the specified trivia room.
     */
    public function edit($id): Response
    {
        $room = TriviaMulti::with(['Coin'])->findOrFail($id);
        $coins = Coin::where('status', 1)->get();
        
        return Inertia::render('Modules/Admin/Trivia/RoomsEdit', [
            'title' => 'Editar Sala de Trivia',
            'room' => $room,
            'coins' => $coins
        ]);
    }

    /**
     * Update the specified trivia room in storage.
     */
    public function update(Request $request, $id)
    {
        $room = TriviaMulti::findOrFail($id);

        $validated = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'players' => 'required|integer|min:2|max:100',
            'prize' => 'required|numeric|min:0',
            'questions_count' => 'required|integer|min:1|max:50',
            'time_per_question' => 'required|integer|min:10|max:300',
        ]);

        if ($validated->fails()) {
            return redirect()->back()
                ->withErrors($validated)
                ->withInput();
        }

        try {
            $room->update([
                'name' => $request->name,
                'players' => $request->players,
                'prize' => $request->prize,
                'questions_count' => $request->questions_count,
                'time_per_question' => $request->time_per_question,
            ]);

            Session::flash('message', 'Sala de trivia actualizada correctamente.');
            Session::flash('type', 'success');
            return redirect()->route('trivia-rooms.index');

        } catch (\Exception $e) {
            Log::error('Error al actualizar sala de trivia.', [
                'error' => $e->getMessage(),
                'usuario' => Auth::id() ?? 'Invitado',
            ]);

            Session::flash('error', 'Hubo un error al actualizar la sala de trivia. Inténtelo de nuevo.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified trivia room from storage.
     */
    public function destroy($id)
    {
        $room = TriviaMulti::findOrFail($id);

        try {
            // Verificar que no haya jugadores activos en la sala
            $activePlayers = DB::table('triv_multi_user')
                ->where('room', $id)
                ->whereIn('lose_win', [0, 1])
                ->count();

            if ($activePlayers > 0) {
                Session::flash('error', 'No se puede eliminar la sala porque tiene jugadores activos.');
                return redirect()->back();
            }

            $room->delete();

            Session::flash('message', 'Sala de trivia eliminada correctamente.');
            Session::flash('type', 'success');
            return redirect()->route('trivia-rooms.index');

        } catch (\Exception $e) {
            Log::error('Error al eliminar sala de trivia.', [
                'error' => $e->getMessage(),
                'usuario' => Auth::id() ?? 'Invitado',
            ]);

            Session::flash('error', 'Hubo un error al eliminar la sala de trivia. Inténtelo de nuevo.');
            return redirect()->back();
        }
    }

    /**
     * Toggle room status.
     */
    public function toggleStatus($id)
    {
        $room = TriviaMulti::findOrFail($id);

        try {
            $newStatus = $room->status == 1 ? 0 : 1;
            $room->update(['status' => $newStatus]);

            Session::flash('message', 'Estado de la sala actualizado correctamente.');
            Session::flash('type', 'success');
            return redirect()->back();

        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de sala de trivia.', [
                'error' => $e->getMessage(),
                'usuario' => Auth::id() ?? 'Invitado',
            ]);

            Session::flash('error', 'Hubo un error al cambiar el estado de la sala. Inténtelo de nuevo.');
            return redirect()->back();
        }
    }
}
