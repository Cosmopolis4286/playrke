<?php

namespace App\Http\Controllers\Games\Trivia\Room;

use App\Http\Controllers\Controller;
use App\Models\Games\Trivia\TriviaRoom;
use App\Models\Currency;
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
        $rooms = TriviaRoom::with(['participants'])
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
        $currencies = Currency::where('status', 1)->get();

        return Inertia::render('Modules/Admin/Trivia/RoomsCreate', [
            'title' => 'Crear Sala de Trivia',
            'currencies' => $currencies
        ]);
    }

    /**
     * Store a newly created trivia room in storage.
     */
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'name'                    => 'required|string|max:255',
            'currency_id'             => 'required|exists:currencies,id',
            'players'                 => 'required|integer|min:2|max:500',
            'prize'                   => 'required|numeric|min:0',
            'questions_count'         => 'required|integer|min:1|max:50',
            'time_per_question'       => 'required|integer|min:10|max:300',
            // Modelo de premio
            'prize_model'             => 'nullable|in:guaranteed,pool',
            'platform_rake_percent'   => 'nullable|numeric|min:0|max:30',
            'sponsor_prize'           => 'nullable|numeric|min:0',
            // Distribución de premios: [{rank: 1, percent: 0.60}, ...]
            'prize_payouts'           => 'nullable|array|min:1',
            'prize_payouts.*.rank'    => 'required|integer|min:1',
            'prize_payouts.*.percent' => 'required|numeric|min:0.01|max:1',
        ]);

        if ($validated->fails()) {
            return redirect()->back()
                ->withErrors($validated)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $currency   = Currency::findOrFail($request->currency_id);
            $prizeModel = $request->input('prize_model', 'guaranteed');

            // Normalizar prize_payouts: suma de percents ≤ 1
            $payouts = $request->input('prize_payouts');
            if ($payouts) {
                $sum = array_sum(array_column($payouts, 'percent'));
                if ($sum > 1.001) {
                    return redirect()->back()
                        ->withErrors(['prize_payouts' => 'La suma de porcentajes no puede superar 100%.'])
                        ->withInput();
                }
                // Ordenar por rank
                usort($payouts, fn($a, $b) => $a['rank'] <=> $b['rank']);
            }

            $room = TriviaRoom::create([
                'code'                  => strtoupper(substr(uniqid(), -6)),
                'host_id'               => auth()->user()->id,
                'title'                 => $request->name,
                'description'           => $request->description ?? null,
                'status'                => 'waiting',
                'max_players'           => $request->players,
                'question_count'        => $request->questions_count,
                'time_per_question'     => $request->time_per_question,
                'entry_currency_type'   => 'fixed',
                'entry_currency_id'     => $currency->id,
                'entry_fee'             => $request->input('entry_fee', 0),
                'prize_type'            => $prizeModel === 'pool' ? 'accumulated' : 'fixed',
                'prize_model'           => $prizeModel,
                'prize_amount'          => $request->prize,
                'prize_currency_id'     => $currency->id,
                'prize_payouts'         => $payouts ? json_encode($payouts) : null,
                'platform_rake_percent' => $request->input('platform_rake_percent', 0),
                'sponsor_prize'         => $request->input('sponsor_prize', 0),
                'scheduled_at'          => $request->scheduled_at ?? now()->addMinutes(30),
            ]);

            DB::commit();

            Session::flash('message', 'Sala de trivia creada correctamente.');
            Session::flash('type', 'success');
            return redirect()->route('trivia-rooms.index');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear sala de trivia.', [
                'error'   => $e->getMessage(),
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
        $room = TriviaRoom::with(['participants'])->findOrFail($id);

        $players = DB::connection('pgsql_trivia')->table('trivia_participants')
            ->where('room_id', $id)
            ->whereNotNull('user_id')
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
        $room = TriviaRoom::findOrFail($id);
        $currencies = Currency::where('status', 1)->get();
        
        return Inertia::render('Modules/Admin/Trivia/RoomsEdit', [
            'title' => 'Editar Sala de Trivia',
            'room' => $room,
            'currencies' => $currencies
        ]);
    }

    /**
     * Update the specified trivia room in storage.
     */
    public function update(Request $request, $id)
    {
        $room = TriviaRoom::findOrFail($id);

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
        $room = TriviaRoom::findOrFail($id);

        try {
            // Verificar que no haya jugadores activos en la sala
            $activePlayers = DB::connection('pgsql_trivia')->table('trivia_participants')
                ->where('room_id', $id)
                ->whereIn('status', ['registered', 'confirmed', 'playing'])
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
        $room = TriviaRoom::findOrFail($id);

        try {
            $newStatus = $room->status === 'waiting' ? 'cancelled' : 'waiting';
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
