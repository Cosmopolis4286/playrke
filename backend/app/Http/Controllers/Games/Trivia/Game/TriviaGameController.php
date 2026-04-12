<?php

namespace App\Http\Controllers\Games\Trivia\Game;

use App\Http\Controllers\Controller;
use App\Models\Games\Trivia\TriviaMulti;
use App\Models\Games\Trivia\TriviaMultiCups;
use App\Models\Games\Trivia\TriviaIndv;
use App\Models\ExtrasUser;
use App\Models\Games;
use App\Models\GameSounds;
use App\Models\Wallet;
use App\Traits\Users\UserTrait;
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
     * Join a trivia room and display the game interface.
     */
    public function room($id): Response
    {
        $user = $this->user(['id']);
        $room = TriviaMulti::findOrFail($id);
        
        // Verificar si el usuario ya está en la sala
        $userInRoom = DB::table('triv_multi_user')
            ->where('room', $id)
            ->where('user', $user->id)
            ->first();

        if (!$userInRoom) {
            // Unir al usuario a la sala
            DB::table('triv_multi_user')->insert([
                'room' => $id,
                'user' => $user->id,
                'joined_at' => now(),
                'lose_win' => 0, // En progreso
            ]);
        }

        return redirect()->route('trivia-games.in-game', $id);
    }

    /**
     * Display the in-game interface.
     */
    public function inGame($id): Response
    {
        $user = $this->user(['id']);
        $wallet = $this->wallet($user->id);
        $game = Games::find(2);
        
        $room = TriviaMulti::findOrFail($id);
        $cupsQuest = TriviaMultiCups::where('room', $id)->get();
        $extrasUser = ExtrasUser::where('user', $user->id)->get();
        
        $infoGame = [
            'game' => $game,
            'room' => $room,
            'user_in_room' => DB::table('triv_multi_user')
                ->where('room', $id)
                ->where('user', $user->id)
                ->first()
        ];

        $sounds = GameSounds::whereIn('id_game', [2, 0])->get();

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
     * Get timer information for a room.
     */
    public function timer($id)
    {
        return TriviaMulti::select('id', 'status', 'fecha', 'hora')
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
     * Get room information for AJAX requests.
     */
    public function roomInfo($id)
    {
        $room = TriviaMulti::with(['Coin', 'Questions'])
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
     * Display the trivia lobby for players.
     * This is the entry point from the sidebar menu.
     */
    public function lobby(): Response
    {
        $user = $this->user(['id']);
        $wallet = $this->wallet($user->id);

        // Obtener monedas disponibles para trivia
        $coins = DB::connection('pgsql_trivia')->table('trivia_rooms')
            ->whereIn('status', ['waiting', 'playing'])
            ->whereNotNull('entry_currency_id')
            ->distinct()
            ->pluck('entry_currency_id');

        $currencies = DB::table('currencies')
            ->where('status', 1)
            ->whereIn('id', $coins)
            ->get();

        return Inertia::render('Trivia/Lobby', [
            'coins' => $currencies,
            'wallet' => $wallet,
        ]);
    }
}
