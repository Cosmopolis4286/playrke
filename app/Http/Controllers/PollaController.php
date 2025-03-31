<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Coin;
use App\Models\PollaDraw;
use App\Models\PollaLotteryResults;
use App\Models\PollaTickets;
use App\Models\PollaTypes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PollaController extends Controller
{
    public function index()
    {
        // Obtener ultimo juego/polla/ en curso
        $draw = PollaDraw::orderByDesc()->first();

        // Definir variables
        $drawID = $draw->id;
        $date = $draw->date;

        $typeGame = PollaTypes::all();
        $titleTypeGame = PollaTypes::getValueById($draw->type_game, 'name');

        $lotteryResults = PollaLotteryResults::getLotteryResult($date);
        $tickets = PollaTickets::all();
        $firstAccumulated = 100;
        $secondAccumulated = 80;
        $weeklyAccumulated = 120;

        $ticketValue = 60;

        $coins = Coin::getByIdForStatus(['=', 1]);
        $tickets = PollaTickets::getAllByDraw($drawID);

        $viewData = [
            'draw' => $draw,
            'titleTypeGame' => $titleTypeGame,
            'lotteryResults' => $lotteryResults,
            'firstccumulated' => $firstAccumulated,
            'secondAccumulated' => $secondAccumulated,
            'weeklyAccumulated' => $weeklyAccumulated,
            'ticketValue' => $ticketValue,
            'coins' => $coins,
            'typeGame' => $typeGame,
            'tickets' => $tickets
        ];

        return view('game')->with($viewData);
    }

    public function create(Request $request)
    {
        DB::beginTransaction();

        try {
            PollaDraw::create([
                'date',
                'game_schedule',
                'close_sale',
                'status',
                'players',
                'plays',
                'paid_win',
                'all_sale',
                'rke_commn',
                'creator_commn',
                'seller_commn',
                'jackpot',
                'code_uuid'
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function update(Request $request, $id){

    }

    public function generatePlay()
    {
        $viewData = ['title' => 'Generar Jugada'];
        return view('generate-play')->with($viewData);
    }

    public function generateTicket(Request $request) {}
}
