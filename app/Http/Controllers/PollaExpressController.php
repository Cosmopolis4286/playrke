<?php

namespace App\Http\Controllers;

use App\Events\BingoRoom as EventsBingoRoom;
use App\Events\GetBingoSort;
use App\Traits\UserVal;
use App\Traits\EncryptRKE;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Coin;
use App\Models\BingoRoom;
use App\Models\BingoSort;
use App\Models\BingoRoomUser;
use App\Models\BingoCtns;
use App\Models\HistGame;
use App\Http\Controllers\Controller;
use App\Models\BingoSortCode;
use App\Models\Country;
use App\Models\PlayerLink;
use App\Traits\BingoTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;

class PollaExpressController extends Controller
{
    use EncryptRKE;
    use UserVal;
    use BingoTrait;

    public function __construct()
    {
        parent::__construct();
    }

    public function game($PJLink)
    {
        $player = PlayerLink::where('player_link', $PJLink)->first();

        if (!$player) return redirect('/');
        $this->hideRoom();

        $room = $player->room;

        $room = BingoRoom::with(['Coin'])
            ->select(['status', 'date', 'time', 'coin', 'jackpot', 'encrypted'])->where('id', $room)->first();

        if (!in_array($room->status, [2, 3, 4])) return redirect('/');
        $title = 'BingoCash';

        if ($room->status == 2) {
            $triviaInit = strtotime($room->date . ' ' . $room->time);
            if (time() >= $triviaInit) BingoRoom::where('id', $room)->update(['status' => 3]);
        }

        $roomCoin = Coin::getMoneyFromCountry($player->country);

        $vanSort = BingoSort::where('room', $room)->where('status', 2)->select('id', 'chip')->first();
        if (!$vanSort) $vanSort = $this->newSort($room, null);

        $sort = ($room->status == 3) ?
            BingoSort::where('room', $room)->where('id', '<=', $vanSort->id)
            ->where('time', '>', time())->get(['code', 'time']) : [];

        $codeEncrypt = BingoSortCode::where('room', $room)->pluck('sym');

        $lastSixChip = $this->getChips$room->encrypted);

        $chipsRest = BingoSort::where('room', $room)->where('time', '>', time())
            ->where('id', '<=', $vanSort->id)->orderBy('chip')->pluck('chip')->sort()->toArray();

        $ctns = BingoCtns::getCtnsByPlayerInRoom($room, $PJLink);

        $allChips =  BingoSort::where('room', $room)->where('id', '<=', $vanSort->id)
            ->where('time', '<', time())->orderByDesc('id')->pluck('chip')->toArray();

        $infoGame = [];
        if ($room->status == 4) $infoGame = $this->getResult($room->encrypted, $PJLink);

        $game = BingoRoom::find($room)->select('date', 'time')->first();
        $timeInitGame = strtotime($game->date . ' ' . $game->time);

        return view('modules.visitor.games.bingo.game')->with([
            'title' => $title,
            'room' => $room,
            'roomCoin' => $roomCoin->name,
            'roomBoat' => $this->convertPrizeValue($room, $roomCoin->id),
            'timeInitRm' => $timeInitGame,
            'PJLink' => $PJLink,
            'chipsRest' => $chipsRest,
            'cartons' => $ctns,
            'sort' => $sort,
            'encrypt' => $codeEncrypt,
            'lastSixChip' => $lastSixChip[0],
            'lastPos' => $lastSixChip[1],
            'allChips' => $allChips,
            'vanSort' => $vanSort->chip,
            'infoGame' => $infoGame,
            'timeServ' => time(),
            // Artisan::call('bingooff:dispatch', ['room' => $room->encrypted])
        ]);
    }

    public function getChips$room)
    {
        $roomId = BingoRoom::where('encrypted', $room)->value('id');
        if ($roomId) {
            $vanSort = BingoSort::where('room', $roomId)->where('status', 2)->first();
            if ($vanSort) {
                $lastSixChip = BingoSort::where('room', $roomId)->where('time', '<=', time())
                    ->where('id', '<=', $vanSort->id)->orderByDesc('id')->take(7)->pluck('chip')->toArray();

                $sizeArr = count($lastSixChip);

                $lastPos = false;
                if ($sizeArr > 1) {
                    $sort = BingoSort::where('room', $roomId)->where('time', '<=', time())
                        ->where('id', '<=', $vanSort->id)->pluck('chip')->toArray();

                    $lastPos = array_search($lastSixChip[$sizeArr - 1], $sort);
                    array_pop($lastSixChip);
                }

                $allChips =  BingoSort::where('room', $room)->where('id', '<=', $vanSort->id)
                    ->where('time', '<', time())->orderByDesc('id')->pluck('chip')->toArray();

                return [$lastSixChip, $lastPos !== false ? $lastPos : 0, time(), $allChips];
            }
        }
    }

    public function getResult($room, $PJLink)
    {
        $room = BingoRoom::where('encrypted', $room)->select('id', 'status', 'encrypted')->first();
        if ($room->status == 3) BingoRoom::where('encrypted', $room)->update(['status' => 4]);

        $winners = BingoRoomUser::where('room', $room->id)->where('winner', '>', 0)->count();
        if (!$winners && $room->status == 4) $this->findWinners($room);

        return $this->getWinners($room, $PJLink);
    }

    public function viewCtn($ctnId)
    {
        return $this->getCtnWin($ctnId);
    }

    public function endGame($room)
    {
        $room = BingoRoom::where('encrypted', $room)->first();
        if ($room->status == 3) {
            BingoRoom::where('encrypted', $room)->update(['status' => 4]);
            EventsBingoRoom::dispatch($room);
            echo json_encode('end');
        }
    }

    public function GetSort($room)
    {
        $room = BingoRoom::where('encrypted', $room)->first();
        $sort = BingoSort::where('room', $room->id)->first();
        if ($sort && !$sort->status) {
            Artisan::call('bingooff:dispatch', ['room' => $room]);
        }
    }

    public function rooms()
    {
        $user = $this->user();
        if ($user->user_type != 2) return redirect()->back();
        $wallet = $this->Wallet($user->id);

        if ($user->user_type == 3) {
            $userComm = User::with(['Commerce'])->where('id', $user->id)->first();
            $commerce = $userComm->commerce;;
        } else if ($user->user_type == 2) {
            $commerce = 1;
        }

        $rooms = BingoRoom::with(['Coin'])->where('commerce', $commerce)->get();
        $coins = Coin::all();
        $country = Country::all();

        return view('modules/commerce/games/bingo/room/index')->with([
            'title' => 'Salas de Bingo',
            'wallet' => $wallet,
            'rooms' => $rooms,
            'country' => $country,
            'coins' => $coins
        ]);
    }

    public function create(Request $request)
    {
        $user = $this->user();
        if ($user->user_type == 2 || $user->user_type == 3) {
            $user_comm = User::with(['Commerce'])->where('id', $user->id)->first();
            if ($request->type_boat == 2) {
                $boat = $request->val_fij;
            } else {
                $boat = 0;
            }

            BingoRoom::create([
                'commerce' => $user_comm->commerce,
                // 'all_in' => 0,
                'status' => 1,
                'date' => $request['date'],
                'time' => $request['time'],
                'replay' => 0,
                'country' => $request['country'],
                'coin' => $request['coin'],
                'price' => $request['val_ctn'],
                'val_songs' => $request['val_songs'],
                'jackpot' => $boat,
                'type_boat' => $request['type_boat'],
                'com' => $request['val_com'],
                'encrypted' => $this->roomEncrypt('bingo_room')
            ]);

            Session::flash('message', 'Sala creada correctamente');
            return redirect()->back();
        }
    }

    public function update(Request $request)
    {
        $bingo = BingoRoom::find($request->id);

        if ($request->type_boat == 2) {
            $boat = $request->val_fij;
        } else {
            $boat = 0;
        }
        $bingo->status = $request['status'];
        $bingo->date = $request['date'];
        $bingo->time = $request['time'];
        // $bingo->replay = 0;
        $bingo->country = $request['country'];
        $bingo->coin = $request['coin'];
        $bingo->price = $request['val_ctn'];
        $bingo->val_songs = $request['val_songs'];
        $bingo->jackpot = $boat;
        $bingo->type_boat = $request['date'];
        $bingo->com = $request['val_com'];
        $bingo->save();

        Session::flash('message', 'La informacion fue actualizada');
        return redirect()->back();
    }

    public function replay(Request $request)
    {
        BingoRoom::where("id", $request->id)->update(['replay' => 1]);
        Session::flash('message', 'Esta sala se publicara cada 24h');
        return redirect()->back();
    }

    public function publish(Request $request)
    {
        BingoRoom::where("id", $request->id)->update(['status' => 2]);
        Session::flash('message', 'La Sala fue publicada');
        return redirect()->back();
    }

    public function delete($id)
    {
        BingoRoom::where("id", $id)->delete();
        Session::flash('message', 'La Sala fue eliminada');
        return redirect()->back();
    }

    public function refundRoom(Request $request) //Reembolsar compras de la sala
    {
        $room = BingoRoom::where('id', $request->id)->first();
        $bg_user = BingoRoomUser::where('room', $request->id)->get();
        foreach ($bg_user as $key => $value) {
            $user_bg = BingoRoomUser::where('room', $request->id)
                ->where('user', $value->user)->first();
            $return_balance = ($user_bg->ctns * $room->price);
            $walleuser_type = Wallet::where('usuario_id', $value->user)->first();
            if ($room->coin == 1) {
                Wallet::where("usuario_id", $value->user)
                    ->update(['usdt' => $walleuser_type->usdt + $return_balance]);
            } else {
                Wallet::where("usuario_id", $value->user)
                    ->update(['saldo' => $walleuser_type->saldo + $return_balance]);
            }
            //Comprobar el historial de jugadas
            HistGame::where('game', 1)->where('room', $request->id)
                ->where('user', $value->id)->update(['gain' => 'Reembolsado']);
            //Comprobar el historial de jugadas
        }
        BingoRoom::where('id', $request->id)->update(['status' => 5]);
        return redirect()->back();
    }

    public function actWn(Request $request) // Generate CTN Win
    {
        $PJLink = $request->PJLink;

        $room = PlayerLink::where('player_link', $PJLink)->value('room');
        if ($room) {
            $resp = $this->newSort($room, $PJLink);
            if ($resp) Session::flash('message', 'Carton Ganador Generado');
        } else {
            Session::flash('message', 'El PlayerLink no existe');
        }
        return redirect()->back();
    }
}
