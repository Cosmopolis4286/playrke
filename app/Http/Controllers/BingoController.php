<?php

namespace App\Http\Controllers;

use App\Events\GetBingoRoom;
use App\Events\GetRoomsBingo;
use App\Events\NewMessageEvent;
use App\Events\UserJoinedRoomBingo;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Message;
use App\Traits\IdEncryptRKE;
use App\Models\BingoRM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\BingoCtns;
use App\Models\BingoOffSortCode;
use App\Models\BingoRoom;
use App\Models\BingoSort;
use App\Models\BingoUserRoom;
use App\Models\Coin;
use App\Models\GameExtras;
use App\Models\HistGame;
use App\Models\Limits;
use App\Traits\UserVal;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Session;
use Intervention\Image\Facades\Image;

class BingoController extends Controller
{
    use UserVal;
    use IdEncryptRKE;

    public function __construct()
    {
        parent::__construct();
    }

    public function allRooms()
    {
        $user = $this->user();
        if ($user->user_type == 2) {
            $title = 'Salas de Bingo';
            $rooms = BingoRoom::with(['Coin', 'Status'])->get();
            $coins = Coin::all();
            $limits = Limits::get();

            return view('modules/admin/bingo/rooms')->with([
                'title' => $title,
                'rooms' => $rooms,
                'wallet' => $this->Wallet($user->id),
                'min_dp_usdt' => $limits[2]->valor,
                'max_dp_usdt' => $limits[11]->valor,
                'coins' => $coins
            ]);
        }
    }

    public function rooms($symCoin)
    {
        $user = $this->user();
        $wallet = $this->Wallet($user->id);
        $coin = strtoupper($symCoin) == 'USDT' ? 1 : $wallet->Coin->id;

        $rooms = BingoRoom::with(['PlayersRoom' => function ($query) use ($user) {
            $query->where('user', $user->id);
        }])->whereIn('status', [2, 3])->where('coin', $coin)
            ->get([
                'id', 'status', 'date', 'time', 'coin', 'price',
                'jackpot', 'players', 'type_boat', 'val_automc'
            ]);

        $date = now()->toDateTimeString();
        $coinRoom = $wallet->Coin->id;
        return view('modules.user.bingo.rooms', compact('wallet', 'date', 'coinRoom', 'rooms'));
    }

    public function updateInfoRooms($coin)
    {
        GetBingoRoom::dispatch($coin);
    }

    // public function getDataRoomBingo($coin)
    // {
    //     $data = BingoRoom::with(['PlayersRoom' => function ($query) use ($user) {
    //         $query->where('user', $user->id);
    //     }])->whereIn('status', [2, 3])->where('coin', $coin)
    //         ->get(['id', 'status', 'date', 'time', 'coin', 'price', 'jackpot', 'players', 'type_boat', 'val_automc']);

    //     GetRoomsBingo::dispatch($data)->toPresence('roomsBingo');

    // }

    //ADMIN INICIO
    public function winners()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->join('monedas', 'monedas.id', 'Wallets.coin')->first();
        if (auth()->user()->user_type == 2) {
            $rooms = DB::table("bingo_room")->join('monedas', 'monedas.id', 'bingo_room.coin')
                ->join('game_status', 'game_status.id', 'bingo_room.status')->orderBy('created_at')->get();
            $coins = DB::table('monedas')->get();
            return view('modules/admin/bingo/winners')->with([
                'rooms' => $rooms,
                'wallet' => $wallet,
                'coins' => $coins
            ]);
        }
    }

    public function store(Request $request)
    {
        if ($request->hasFile('file')) {
            $filename = $request->file->getClientOriginalName();
        }
        if ($request->type_boat == 2) {
            $boat = $request->val_fij;
        } else {
            $boat = 0;
        }
        $encrypt_room = $this->roomEncrypt('bingo_room');
        BingoRM::create([
            'date' => $request['date'],
            'time' => $request->time,
            'coin' => $request['coin'],
            'price' => $request['val_ctn'],
            'val_automc' => $request['val_automc'],
            'val_songs' => $request['val_songs'],
            'type_boat' => $request['type_boat'],
            'jackpot' => $boat,
            'com' => $request['val_com'],
            'status' => 1,
            'encrypted' => $encrypt_room
        ]);
        Session::flash('message', 'Sala creada correctamente');
        return redirect()->back();
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'date' => 'required',
            'time' => 'required',
            'coin' => 'required',
            'val_ctn' => 'required',
            'val_automc' => 'required',
            'val_songs' => 'required',
            'type_boat' => 'required',
            'val_com' => 'required'
        ]);

        if ($request->hasFile('file')) {
            $filename = $request->file->getClientOriginalName();
        }
        if ($request['type_boat'] == 2) {
            $boat = $request['val_fij'];
        } else {
            $boat = 0;
        }
        $sala = BingoRM::find($request->id);
        //$sala->img = $filename;
        $sala->date = $request->input('date');
        $sala->time = $request->input('time');
        $sala->coin = $request->input('coin');
        $sala->price = $request->input('val_ctn');
        $sala->val_automc = $request->input('val_automc');
        $sala->val_songs = $request->input('val_songs');
        $sala->type_boat = $request->input('type_boat');
        $sala->jackpot = $boat;
        $sala->com = $request->input('val_com');
        $sala->save();
        Session::flash('message', 'La Sala fue actualizada');
        return redirect()->back();
    }

    public function replay(Request $request)
    {
        BingoRM::where("id", $request->id)->update(['replay' => 1]);
        Session::flash('message', 'Esta sala se publicara cada 24h');
        return redirect()->back();
    }

    public function publish(Request $request)
    {
        BingoRM::where("id", $request->id)->update(['status' => 2]);
        Session::flash('message', 'La Sala fue publicada');
        return redirect()->back();
    }

    public function delete(Request $request)
    {
        BingoRM::where("id", $request->id)->delete();
        Session::flash('message', 'La Sala fue eliminada');
        return redirect()->back();
    }

    public function refund_room(Request $request) //Reembolsar compras de la sala
    {
        $room = DB::table("bingo_room")->where('id', $request->id)->first();
        $bg_user = DB::table('bingo_room_user')->where('room', $request->id)->get();
        foreach ($bg_user as $key => $value) {
            $user_bg = DB::table('bingo_room_user')->where('room', $request->id)
                ->where('user', $value->user)->first();
            $return_balance = ($user_bg->ctns * $room->price);
            if ($user_bg->auto_mc > 0) {
                $return_balance += $room->val_automc;
            }
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
        DB::table("bingo_room")->where('id', $request->id)->update(['status' => 5]);
        return redirect()->back();
    }
    // ADMIN FIN

    public function timer($id)
    {
        $info = DB::table("bingo_room")
            ->select(
                'bingo_room.id',
                'bingo_room.date',
                'bingo_room.time'
            )->whereIn("status", [2, 3])->where('coin', $id)->get();
        return $info;
    }

    public   function time_serv()
    {
        $this->updateInfoRooms(1);
        $user = $this->user();
        $date = date('Y-m-d H:i:s');
        echo json_encode($date);
    }

    public function consult_players(Request $request)
    {
        $info = DB::table("bingo_room_user")
            ->join('users', 'users.id', 'bingo_room_user.user')
            ->where('room', $request->room)->orderBy('username')->get();

        $info_user = ['name' => [], 'foto' => []];
        foreach ($info as $key => $value) {
            array_push($info_user['name'], $value->username);
            array_push($info_user['foto'], $value->foto);
        }
        echo json_encode(array('players' => $info_user));
    }

    public function changeStatusRoom($id)
    {
        $rooms = [];
        $roomActive = BingoRoom::whereIn('status', [2, 3])->get('id', 'date', 'time', 'close_room');
        if (count($roomActive)) {
            foreach ($roomActive as $value) {
                $dataTime = strtotime($value->date . ' ' . $value->time);
                if (time() >= $dataTime) {
                    $sort = BingoSort::where('room', $value->id)->pluck('time');
                    if (!count($sort)) {
                        BingoRoom::where('id', $value->id)->update(['status' => 4]);
                        $rooms[] = $value->id;
                    } else {
                        if (
                            $sort[74] && $sort[74] + 9 <= time() ||
                            ($value->close_room && time() >= $value->close_room + 9)
                        ) {
                            BingoRoom::where('id', $value->id)->update(['status' => 4]);
                            $rooms[] = $value->id;
                        }
                    }
                }
            }
        }
        if ($id) {
            $room = BingoRoom::where('id', $id)->select('id', 'date', 'time', 'status')->first();

            if ($room->status == 2 && strtotime($room->date . ' ' . $room->time) <= time()) {
                BingoRoom::where('id', $id)->update(['status' => 3]);
            }

            $userRoom = BingoRoom::with(['PlayersRoom' => function ($query) {
                $query->where('user',  auth()->user()->id);
            }])->where('id', $id)->where('status', 3)->select('id')->first();

            echo json_encode(array('van' => $userRoom->id, 'rfs' => $rooms));
        }
    }

    public function encrypt_sort($id)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < 10; $i++) {
            $encrypt = '';
            $sort_code = BingoOffSortCode::where('room', $id)->pluck('sym')->toArray();
            if (count($sort_code) < 10) {
                do {
                    $index = rand(0, strlen($characters) - 1);
                    $encrypt .= $characters[$index];
                } while (strlen($encrypt) < 3 || in_array($encrypt, $sort_code));
                BingoOffSortCode::insertGetId([
                    'num' => count($sort_code) + 1, 'sym' => $encrypt, 'room' => $id
                ]);
            }
        }
    }

    public function round_sort($id, $ctn_win)
    {
        $chips = [];

        $sort = DB::table("bingo_sort")->where('room', $id)->first();
        $room = DB::table("bingo_room")->where('id', $id)->first();
        $data_time = strtotime($room->date . $room->time);
        $chp = 0;
        if ($sort == null) {
            $this->encrypt_sort($id);
            while ($chp <= 74) {
                $rnd = mt_rand(1, 75);
                if (in_array($rnd, $chips)) {
                    continue;
                } else {
                    $nm_encoded = '';
                    $num_string = "$rnd";
                    $separt_srt = strlen($num_string);
                    for ($i = 0; $i < $separt_srt; $i++) {
                        $sort_code = BingoOffSortCode::where('room', $id)->pluck('num')->toArray();
                        if ($num_string[$i] == 0) {
                            $num_code = 10;
                        } else {
                            $num_code = $num_string[$i];
                        }
                        if (in_array($num_code, $sort_code)) {
                            $sort_code = BingoOffSortCode::where('room', $id)->where('num', $num_code)->first();
                            $nm_encoded .= $sort_code->sym;
                        }
                    }
                    $sort = DB::table("bingo_sort")->where('room', $id)->orderByDesc('id')->first();
                    if ($sort == null) {
                        DB::table("bingo_sort")->insert([
                            "chip" => $rnd, "code" => $nm_encoded, "room" => $id, "time" => $data_time + 12
                        ]);
                    } else {
                        DB::table("bingo_sort")->insert([
                            "chip" => $rnd, "code" => $nm_encoded, "room" => $id, "time" => $sort->time + 7
                        ]);
                    }
                    array_push($chips, $rnd);
                    $chp++;
                }
            }
        }
        // else if ($sort != null && $ctn_win != null) {
        //     $new_srt = $ctn_win;
        //     $sort = DB::table("bingo_sort")->where('room', $id)->get();
        //     array_unshift($new_srt, $sort[0]->chip, $sort[1]->chip, $sort[2]->chip, $sort[3]->chip);
        //     $id_srt = $sort[0]->id + 4;
        //     while ($chp <= 46) {
        //         $rnd = mt_rand(1, 75);
        //         if (in_array($rnd, $new_srt)) {
        //             continue;
        //         } else {
        //             array_push($new_srt, $rnd);
        //             for ($i = 0; $i < count($new_srt); $i++) {
        //                 $sort = DB::table("bingo_sort")->where('room', $id)->where('id', $id_srt)
        //                     ->update(["chip" => $new_srt[$i]]);
        //                 $id_srt += 1;
        //             }
        //             $chp++;
        //         }
        //     }
        // }
    }

    public function van_sort($id)
    {
        $vn_chips = [];
        $sort = DB::table("bingo_sort")->where('room', $id)->get();
        $ctns = DB::table("bingo_ctns")->where('room', $id)->pluck('id');
        $z = 0;
        for ($i = 0; $i < count($ctns); $i++) {
            // Recorro todos los cartones de esa sala
            $ctn = DB::table("bingo_ctns")->where('id', $ctns[$i])->first();
            $cart = [ // Creo un array de los numeros de cada carton independiente
                $ctn->b1, $ctn->b2, $ctn->b3, $ctn->b4, $ctn->b5,
                $ctn->i1, $ctn->i2, $ctn->i3, $ctn->i4, $ctn->i5,
                $ctn->n1, $ctn->n2, $ctn->n3, $ctn->n4,
                $ctn->g1, $ctn->g2, $ctn->g3, $ctn->g4, $ctn->g5,
                $ctn->o1, $ctn->o2, $ctn->o3, $ctn->o4, $ctn->o5
            ];
            for ($x = 74; $x >= 0; $x--) {
                if (in_array($sort[$x]->chip, $cart)) {
                    if (in_array($sort[$x]->chip, $vn_chips) == false) {
                        array_push($vn_chips, $sort[$x]->chip);
                    }
                    break;
                }
            }
        }
        $chip_van = DB::table("bingo_sort")->whereIn('chip', $vn_chips)
            ->orderBy('id')->where('room', $id)->first();
        $pos = '';
        for ($z = 0; $z < 75; $z++) {
            if ($chip_van->chip == $sort[$z]->chip) {
                $pos = $z;
                break;
            }
        }
        //Funcion para limpiar tiempo de sorteo despues de la vandera
        $sort = DB::table("bingo_sort")->where('room', $id)->get();
        if ($sort[$pos]->chip_win == null) {
            DB::table("bingo_sort")->where('id', $sort[$pos]->id)
                ->where('room', $id)->update(['chip_win' => 'VAN']);
            for ($i = $pos; $i < 75; $i++) {
                if ($pos < 74 && $sort[73]->time != null) {
                    if ($sort[$i]->id > $sort[$pos]->id) {
                        DB::table("bingo_sort")->where('id', $sort[$i]->id)
                            ->where('room', $id)->update(['time' => null]);
                    }
                }
            }
        }
        //Funcion para limpiar tiempo de sorteo despues de la vandera
        return $chip_van;
    }

    function generateRandomNumbers($start, $end, $count)
    {
        $numbers = [];
        while (count($numbers) < $count) {
            $randomNumber = mt_rand($start, $end);
            if (!in_array($randomNumber, $numbers)) {
                $numbers[] = $randomNumber;
            }
        }
        return $numbers;
    }

    public function carton_store(Request $request)
    {
        $room = $request->bingoid;
        $room = BingoRoom::where('status', 2)->where('id', $room)->first();
        if (!$room)  return redirect()->back();

        $user = $this->user();
        $wallet = $this->Wallet($user->id);

        $userBingo = BingoUserRoom::where('room', $room)->where("user", $user->id)->first();

        $mysaldo = $wallet->saldo;
        if ($room->coin == 1) $mysaldo =  $wallet->usdt;

        $mont_ctn = $request->montop;

        if ($mysaldo >= $mont_ctn) {
            $x = 1;
            do {
                $nwCtn = [];
                $nwCtn = array_merge($nwCtn, $this->generateRandomNumbers(1, 15, 5));
                $nwCtn = array_merge($nwCtn, $this->generateRandomNumbers(16, 30, 5));
                $nwCtn = array_merge($nwCtn, $this->generateRandomNumbers(31, 45, 4));
                $nwCtn = array_merge($nwCtn, $this->generateRandomNumbers(46, 60, 5));
                $nwCtn = array_merge($nwCtn, $this->generateRandomNumbers(61, 75, 5));

                $existing_ctns = BingoCtns::where('room', $room)->pluck('id')
                    ->map(function ($id) {
                        $val = BingoCtns::where('id', $id)->first();
                        return [
                            $val->b1, $val->b2, $val->b3, $val->b4, $val->b5,
                            $val->i1, $val->i2, $val->i3, $val->i4, $val->i5,
                            $val->n1, $val->n2, $val->n3, $val->n4,
                            $val->g1, $val->g2, $val->g3, $val->g4, $val->g5,
                            $val->o1, $val->o2, $val->o3, $val->o4, $val->o5,
                        ];
                    });
                $ctnExists = $existing_ctns->contains($nwCtn);

                if (!$ctnExists) {
                    BingoCtns::insert([
                        'room' => $room, 'user' => $user->id, 'b1' => $nwCtn[0],
                        'b2' => $nwCtn[1], 'b3' => $nwCtn[2], 'b4' => $nwCtn[3], 'b5' => $nwCtn[4],
                        'i1' => $nwCtn[5], 'i2' => $nwCtn[6], 'i3' => $nwCtn[7], 'i4' => $nwCtn[8],
                        'i5' => $nwCtn[9], 'n1' => $nwCtn[10], 'n2' => $nwCtn[11], 'n3' => $nwCtn[12],
                        'n4' => $nwCtn[13], 'g1' => $nwCtn[14], 'g2' => $nwCtn[15], 'g3' => $nwCtn[16],
                        'g4' => $nwCtn[17], 'g5' => $nwCtn[18], 'o1' => $nwCtn[19], 'o2' => $nwCtn[20],
                        'o3' => $nwCtn[21], 'o4' => $nwCtn[22], 'o5' => $nwCtn[23]
                    ]);
                    $x++;
                }
            } while ($x <= $request->cantidad);

            if ($room->coin == 1) {
                Wallet::where("usuario_id", $user->id)->update(['usdt' => $wallet->usdt - $mont_ctn]);
            } else {
                Wallet::where("usuario_id", $user->id)->update(['saldo' => $wallet->saldo - $mont_ctn]);
            }
            $cant_players = $room->players;
            if (!$userBingo) {
                $cant_players = $room->players + 1;
                BingoUserRoom::create([
                    'user' => $user->id, 'room' => $room,
                    'ctns' => $request->cantidad
                ]);
            } else {
                BingoUserRoom::where('room', $room)->where("user", $user->id)
                    ->update(['ctns' => $userBingo->ctns + $request->cantidad]);
            }
            if ($request->auto_mc == 1) {
                BingoUserRoom::where('room', $room)->where("user", $user->id)->update(['auto_mc' => 1]);
                $mont_ctn -= $room->val_automc;
                $walletADM = Wallet::where("usuario_id", 1)->first();
                if ($room->coin == 1) {
                    Wallet::where("usuario_id", 1)->update([
                        'usdt' => $walletADM->usdt + $room->val_automc
                    ]);
                } else {
                    Wallet::where("usuario_id", 1)->update([
                        'saldo' => $walletADM->saldo + $room->val_automc
                    ]);
                }
            }
            if ($request->type_bt == 1) {
                $boat_aum = $mont_ctn;
            } else if ($request->type_bt == 2) {
                $boat_aum = 0;
            }
            BingoRoom::where("id", $room)
                ->update([
                    'players' => $cant_players,
                    'jackpot' => $room->jackpot + $boat_aum,
                    'ctns' => $room->ctns + $request->cantidad
                ]);

            $hist = HistGame::where('game', 1)->where('room', $room)
                ->where('user', $user->id)->first();
            if (!$hist) {
                HistGame::create([
                    'user' => $user->id, 'date' => $room->date, 'time' => $room->time,
                    'game' => 1, 'room' => $room, 'coin' => $room->coin
                ]);
            }
            if ($request->ctn_win == 1) {
                $ctn_win = $nwCtn;
            } else {
                $ctn_win = null;
            }

            $sort = BingoSort::where('room', $room)->pluck('chip');
            if (!count($sort))  $this->round_sort($room, $ctn_win);
        }
        $this->updateInfoRooms($room->coin);
        return redirect()->back()->with($message = array('message' => 'Su compra fué procesada', 'buy' => 1));
    }


    public   function  game($room)
    {
        $user = $this->user()->id;
        $wallet = $this->Wallet($user);
        $this->check_paid_win();

        $room = BingoRoom::with(['PlayersRoom' => function ($query) use ($user) {
            $query->where('user', $user);
        }])->where('id', $room)->where("status", 3)->first();

        if (!$room) return redirect()->back();

        Broadcast::channel('roomBingo.' . $room, function ($user, $id) {
            return ['id' => $user];
        });

        $message = "¡Hola! Bienvenido a la sala de bingo.";
        UserJoinedRoomBingo::dispatch($user, $room, $message);

        // $sort = BingoSort::where('room', $room)->get();

        $vanSort = BingoSort::where('room', $room)->where('status', 2)->first();
        if (!$vanSort) $vanSort = $this->round_sort($room, null);

        dd($vanSort);

        $sort = BingoSort::where('room', $room)->select('chip', 'time')
            ->where('id', '<=', $vanSort->id)->where('time', '>=', time())->get()->toArray();

        $van_sort = $this->van_sort($room);
        $chip_win = $this->chip_win($room);

        $ctns = DB::table("bingo_ctns")->where('room', $room)->where('user', $user)->get();
        $ctns = DB::table("bingo_ctns")->where('room', $room)->where('user', $user)->pluck('id');
        $arr = $this->getChips($room);
        $arr_all_chips = $this->getAllChips($room);
        $chips_gm_rest = [];
        for ($i = 0; $i < 75; $i++) {
            $chp_time = $sort[$i]->time;
            if ($chp_time >= time()) {
                if (in_array($sort[$i]->chip, $chips_gm_rest)) {
                    continue;
                } else {
                    array_push($chips_gm_rest, $sort[$i]->chip);
                }
            }
        }
        sort($chips_gm_rest);
        $sort_code = BingoOffSortCode::where('room', $room)->pluck('sym');
        $cp_token = BingoSort::where('room', $room)->pluck('code');
        $tm_nkt = BingoSort::where('room', $room)->pluck('time');

        $tm_stop = $room->tm_stop;
        if ($tm_stop != null) {
            $tm_stop = $room->tm_stop - time();
            if ($tm_stop < 0) {
                $tm_stop = 0;
            }
        }

        return view('modules.user.bingo.game')->with([
            'wallet' => $wallet,
            'room' => $room->id,
            'room' => $room,
            'cartons' => $ctns,
            'ctns' => $ctns,
            'sort' => $sort,
            'lt_chips' => $arr[0],
            'all_chips' => $arr_all_chips,
            'chips_gm_rest' => $chips_gm_rest,
            'idtm_srt' => $sort_code,
            'cp_tkn' => $cp_token,
            'tm_nkt' => $tm_nkt,
            'last_ps' => $arr[1],
            'vn_srt' => $van_sort,
            'chip_win' => $chip_win,
            'tm_stop' => $tm_stop,
            'tm_serv' => time()
        ]);
    }

    public function check_paid_win()
    {
        $user = auth()->user()->id;
        $user_win = DB::table("bingo_room_user")
            ->join('bingo_room', 'bingo_room.id', 'bingo_room_user.room')->where('user', $user)
            ->where('paid', 0)->where('bingo_room_user.bg_all', 1)->orderByDesc('room')->first();
        if (!empty($user_win)) {
            $pj_bg_all = DB::table("bingo_room_user")
                ->where('room', $user_win->room)->where('bg_all', 1)->count();

            $hist_gm = HistGame::where('game', 1)->where('room', $user_win->room)
                ->where('user', $user_win->user)->first();

            if ($hist_gm->gain == null) {
                $pz_songs = 0;
                $bot = $user_win->jackpot;
                $comision = ($bot * $user_win->com / 100);
                $walletADM = Wallet::where("usuario_id", 1)->first();
                if ($user_win->prize_sm > 0) {
                    $pz_songs = $user_win->val_songs * $user_win->prize_sm;
                }
                $gain = ($bot - $comision + $pz_songs) / $pj_bg_all;
                $wallet = Wallet::where("usuario_id", $user_win->user)->first();
                if ($user_win->coin != 1) {
                    Wallet::where("usuario_id", $user_win->user)
                        ->update(['saldo' => $wallet->saldo + $gain]);
                    Wallet::where("usuario_id", 1)
                        ->update(['saldo' => $walletADM->saldo + $comision]);
                } else {
                    Wallet::where("usuario_id", $user_win->user)
                        ->update(['usdt' => $wallet->usdt + $gain]);
                    Wallet::where("usuario_id", 1)
                        ->update(['usdt' => $walletADM->usdt + $comision]);
                }
                HistGame::where('game', 1)->where('room', $user_win->room)
                    ->where('user', $user_win->user)->update(['gain' => $gain]);
            }
        }
    }

    public function chip_win($id)
    {
        $wnchip = null;
        $chp_win = DB::table("bingo_sort")
            ->where('room', $id)->where('chip_win', '!=', null)
            ->where('chip_win', '!=', 'VAN')->first();
        if ($chp_win != null) {
            $wnchip = $chp_win->chip;
        }
        return $wnchip;
    }

    public function getChips(($room)
    {
        $srt = DB::table("bingo_sort")->where('room', $room)->get();
        $chip_win = $this->chip_win($room);
        if ($chip_win != null) {
            $ltchip_win = DB::table("bingo_sort")
                ->where('room', $room)->where('chip_win', '!=', null)
                ->where('chip_win', '!=', 'VAN')->first();
        }
        $chips_gm = [];
        $last_ps = '';
        for ($i = 74; $i >= 0; $i--) {
            $chp_time = $srt[$i]->time;
            if ($chp_time <= time() && $chp_time != null) {
                if (in_array($srt[$i]->chip, $chips_gm)) {
                    continue;
                } else {
                    if ($chip_win == null) {
                        array_push($chips_gm, $srt[$i]->chip);
                    } else {
                        if ($srt[$i]->time <= $ltchip_win->time) {
                            array_push($chips_gm, $srt[$i]->chip);
                        } else {
                            continue;
                        }
                    }
                    if ($last_ps == '') {
                        $last_ps = $i;
                    }
                }
                if (count($chips_gm) > 6) {
                    array_pop($chips_gm);
                    break;
                }
            }
        }

        return [$chips_gm, $last_ps, time()];
    }

    public function getAllChips($room)
    {
        $srt = DB::table("bingo_sort")->where('room', $room)->get();
        $chip_win = $this->chip_win($room);
        if ($chip_win != null) {
            $ltchip_win = DB::table("bingo_sort")
                ->where('room', $room)->where('chip_win', '!=', null)
                ->where('chip_win', '!=', 'VAN')->first();
        }
        $chips_gm = [];
        for ($i = 74; $i >= 0; $i--) {
            $chp_time = $srt[$i]->time;
            if ($chp_time <= time() && $chp_time != null) {
                if (in_array($srt[$i]->chip, $chips_gm)) {
                    continue;
                } else {
                    if ($chip_win == null) {
                        array_push($chips_gm, $srt[$i]->chip);
                    } else {
                        if ($srt[$i]->time <= $ltchip_win->time) {
                            array_push($chips_gm, $srt[$i]->chip);
                        } else {
                            continue;
                        }
                    }
                }
            }
        }
        return $chips_gm;
    }

    public function penalty($room, $user)
    {
        $tm_stop = time() + 12; //Tiempo de Penalizacion
        DB::table('bingo_room_user')->where('room', $room)
            ->where('user', $user)->update(['tm_stop' => $tm_stop]);
        return $tm_stop;
    }

    public function check_last_chp($room, $ctn)
    {
        $sort = DB::table("bingo_sort")->where('room', $room)->orderBy('id', 'desc')->pluck('chip')->toArray();

        $ctn = DB::table("bingo_ctns")->where('id', $ctn)->first();
        $cart = [
            $ctn->b1, $ctn->b2, $ctn->b3, $ctn->b4, $ctn->b5,
            $ctn->i1, $ctn->i2, $ctn->i3, $ctn->i4, $ctn->i5,
            $ctn->n1, $ctn->n2, $ctn->n3, $ctn->n4,
            $ctn->g1, $ctn->g2, $ctn->g3, $ctn->g4, $ctn->g5,
            $ctn->o1, $ctn->o2, $ctn->o3, $ctn->o4, $ctn->o5
        ];

        $last_chip = array_intersect($sort, $cart);
        return (!empty($last_chip)) ? reset($last_chip) : '';
    }

    //COMPROBAR BINGO
    public function check_ctn(Request $request)
    {
        $user = auth()->user()->id;

        $cartn = DB::table("bingo_ctns")->where('user', $user)->where('id', $request->ctn)->first();
        $room = $cartn->room;
        $nm_chps = $request->nm_chps;
        $dgn_1 = [$cartn->b1, $cartn->i2, $cartn->g4, $cartn->o5];
        $dgn_2 = [$cartn->b5, $cartn->i4, $cartn->g2, $cartn->o1];
        $four_corners = [$cartn->b1, $cartn->o1, $cartn->b5, $cartn->o5];
        $big_cross = [$cartn->b3, $cartn->n1, $cartn->o3, $cartn->n4];
        $small_cross = [$cartn->i3, $cartn->n2, $cartn->n3, $cartn->g3];
        $column_1 = [$cartn->b1, $cartn->b2, $cartn->b3, $cartn->b4, $cartn->b5];
        $column_2 = [$cartn->i1, $cartn->i2, $cartn->i3, $cartn->i4, $cartn->i5];
        $column_3 = [$cartn->n1, $cartn->n2, $cartn->n3, $cartn->n4];
        $column_4 = [$cartn->g1, $cartn->g2, $cartn->g3, $cartn->g4, $cartn->g5];
        $column_5 = [$cartn->o1, $cartn->o2, $cartn->o3, $cartn->o4, $cartn->o5];
        $row_1 = [$cartn->b1, $cartn->i1, $cartn->n1, $cartn->g1, $cartn->o1];
        $row_2 = [$cartn->b2, $cartn->i2, $cartn->n2, $cartn->g2, $cartn->o2];
        $row_3 = [$cartn->b3, $cartn->i3, $cartn->g3, $cartn->o3];
        $row_4 = [$cartn->b4, $cartn->i4, $cartn->n4, $cartn->g4, $cartn->o4];
        $row_5 = [$cartn->b5, $cartn->i5, $cartn->n4, $cartn->g5, $cartn->o5];
        $ctn_full = [
            $cartn->b1, $cartn->b2, $cartn->b3, $cartn->b4, $cartn->b5,
            $cartn->i1, $cartn->i2, $cartn->i3, $cartn->i4, $cartn->i5,
            $cartn->n1, $cartn->n2, $cartn->n3, $cartn->n4,
            $cartn->g1, $cartn->g2, $cartn->g3, $cartn->g4, $cartn->g5,
            $cartn->o1, $cartn->o2, $cartn->o3, $cartn->o4, $cartn->o5
        ];
        $sort = DB::table("bingo_sort")->where('room', $room)->get();
        $chips = [];
        $time_bing = $request->ck_time;

        foreach ($sort as $item) {
            if ($item->time != '' && $item->time <= $time_bing) {
                $chips[] = $item->chip;
            }
        }
        $room = DB::table('bingo_room')->where('id', $room)->first();
        $rm_user = DB::table('bingo_room_user')->where('room', $room)
            ->where('user', $user)->first();

        if ($room->status == 3) {
            if ($nm_chps <= 23) {
                if ($room->val_songs > 0) {
                    $sng = 1; //No Hay Bingo Penalty
                    $c_dgn_1 = array_diff($dgn_1, $chips);
                    $c_dgn_2 = array_diff($dgn_2, $chips);
                    if (($c_dgn_1 == [] || $c_dgn_2 == []) && $rm_user->dgn == 0) {
                        if ($room->dgn == 0) {
                            $sng = 0; //Ganaste Premio Menor
                            DB::table('bingo_room')->where('id', $room)->update(['dgn' => 1]);
                            DB::table('bingo_room_user')->where('room', $room)
                                ->where('user', $user)->update([
                                    'dgn' => 1, 'prize_sm' => $rm_user->prize_sm + 1,
                                    'winner' => 1, 'time_prize' => time()
                                ]);
                        } else {
                            $sng = 3; //Aviso Premio Canjeado
                            DB::table('bingo_room_user')->where('room', $room)
                                ->where('user', $user)->update(['dgn' => 2]);
                        }
                    }
                    $c_four_cner = array_diff($four_corners, $chips);
                    if ($c_four_cner == [] && $rm_user->fr_crns == 0) {
                        if ($room->fr_crns == 0) {
                            $sng = 0; //Ganaste Premio Menor
                            DB::table('bingo_room')->where('id', $room)->update(['fr_crns' => 1]);
                            $rm_user = DB::table('bingo_room_user')->where('room', $room)
                                ->where('user', $user)->first();
                            DB::table('bingo_room_user')->where('room', $room)
                                ->where('user', $user)->update([
                                    'fr_crns' => 1, 'prize_sm' => $rm_user->prize_sm + 1,
                                    'winner' => 1, 'time_prize' => time()
                                ]);
                        } else {
                            $sng = 3; //Aviso Premio Canjeado
                            DB::table('bingo_room_user')->where('room', $room)
                                ->where('user', $user)->update(['fr_crns' => 2]);
                        }
                    }
                    $c_sm_cross = array_diff($small_cross, $chips);
                    if ($c_sm_cross == [] && $rm_user->sm_cross == 0) {
                        if ($room->sm_cross == 0) {
                            $sng = 0; //Ganaste Premio Menor
                            DB::table('bingo_room')->where('id', $room)->update(['sm_cross' => 1]);
                            $rm_user = DB::table('bingo_room_user')->where('room', $room)
                                ->where('user', $user)->first();
                            DB::table('bingo_room_user')->where('room', $room)
                                ->where('user', $user)->update([
                                    'sm_cross' => 1, 'prize_sm' => $rm_user->prize_sm + 1,
                                    'winner' => 1, 'time_prize' => time()
                                ]);
                        } else {
                            $sng = 3; //Aviso Premio Canjeado
                            DB::table('bingo_room_user')->where('room', $room)
                                ->where('user', $user)->update(['sm_cross' => 2]);
                        }
                    }
                    $c_bg_cross = array_diff($big_cross, $chips);
                    if ($c_bg_cross == [] && $rm_user->bg_cross == 0) {
                        if ($room->bg_cross == 0) {
                            $sng = 0; //Ganaste Premio Menor
                            DB::table('bingo_room')->where('id', $room)->update(['bg_cross' => 1]);
                            $rm_user = DB::table('bingo_room_user')->where('room', $room)
                                ->where('user', $user)->first();
                            DB::table('bingo_room_user')->where('room', $room)
                                ->where('user', $user)->update([
                                    'bg_cross' => 1, 'prize_sm' => $rm_user->prize_sm + 1,
                                    'winner' => 1, 'time_prize' => time()
                                ]);
                        } else {
                            $sng = 3; //Aviso Premio Canjeado
                            DB::table('bingo_room_user')->where('room', $room)
                                ->where('user', $user)->update(['bg_cross' => 2]);
                        }
                    }
                    $c_colum1 = array_diff($column_1, $chips);
                    $c_colum2 = array_diff($column_2, $chips);
                    $c_colum3 = array_diff($column_3, $chips);
                    $c_colum4 = array_diff($column_4, $chips);
                    $c_colum5 = array_diff($column_5, $chips);
                    $c_row1 = array_diff($row_1, $chips);
                    $c_row2 = array_diff($row_2, $chips);
                    $c_row3 = array_diff($row_3, $chips);
                    $c_row4 = array_diff($row_4, $chips);
                    $c_row5 = array_diff($row_5, $chips);
                    if (
                        $rm_user->bg_line == 0 && ($c_colum1 == [] || $c_colum2 == [] ||
                            $c_colum3 == [] || $c_colum4 == [] || $c_colum5 == [] ||
                            $c_row1 == [] || $c_row2 == [] || $c_row3 == []
                            || $c_row4 == [] || $c_row5 == [])
                    ) {
                        if ($room->bg_line == 0) {
                            $sng = 0; //Ganaste Premio Menor
                            DB::table('bingo_room')->where('id', $room)->update(['bg_line' => 1]);
                            $rm_user = DB::table('bingo_room_user')->where('room', $room)
                                ->where('user', $user)->first();
                            DB::table('bingo_room_user')->where('room', $room)
                                ->where('user', $user)->update([
                                    'bg_line' => 1, 'prize_sm' => $rm_user->prize_sm + 1,
                                    'winner' => 1, 'time_prize' => time()
                                ]);
                        } else {
                            $sng = 3; //Aviso Premio Canjeado
                            DB::table('bingo_room_user')->where('room', $room)
                                ->where('user', $user)->update(['bg_line' => 2]);
                        }
                    }
                } else {
                    $sng = 1; //No Hay Bingo Penalty    
                }
            } else {
                if ($rm_user->bg_all == 0) {
                    if (empty(array_diff($ctn_full, $chips))) {
                        if ($room->close_room == null) {
                            $last_chip = $this->check_last_chp($room, $request->ctn);
                            DB::table('bingo_room')->where('id', $room)->update(['close_room' => time() + 7]);
                            DB::table('bingo_sort')->where('room', $room)
                                ->where('chip', $last_chip)->update(['chip_win' => date('d-m-Y H:i:s')]);
                            $sng = 4; //Ganador PremioMayor
                        } else {
                            if ($room->close_room - time() > 0) {
                                $sng = 4; //Ganador PremioMayor
                            } else {
                                $sng = 3; //El jackpot fue repartido
                            }
                        }
                    } else {
                        $sng = 1; //No Hay Bingo Penalty
                    }
                }
            }
        } else {
            $sng = 2; //Juego Finalizado
        }
        if (isset($sng)) {
            if ($sng == 0) {
                echo json_encode(array('van' => 0));
            } else if ($sng == 1) {
                $tm_stop = $this->penalty($room, $user);
                echo json_encode(array('van' => 1, 'tm_stop' => $tm_stop));
            } else if ($sng == 2) {
                echo json_encode(array('van' => 2));
            } else if ($sng == 3) {
                echo json_encode(array('van' => 3));
            } else if ($sng == 4) {
                DB::table('bingo_room_user')->where('room', $room)
                    ->where('user', $user)->update([
                        'winner' => 1, 'bg_all' => 1, 'ctn_win' => $cartn->id,
                        'chip_bing' => $request->chip_lt, 'time_prize' => time()
                    ]);
                echo json_encode(array('van' => 4));
            }
        }
    }

    public function info_win($id)
    {
        $user = auth()->user()->id;
        $room = DB::table('bingo_room')->where('id', $id)->first();
        $srt = DB::table("bingo_sort")->where('room', $id)->get();
        $bot = $room->jackpot;
        $pay_gain = 0;
        $pm_wins = 1;
        $comision = ($bot * $room->com / 100);

        $pj_bg_all = BingoUserRoom::where('room', $id)->where('bg_all', 1)->count();
        $paid_user = BingoUserRoom::where('room', $id)->where('paid', 1)->count();

        if ($pj_bg_all == $paid_user && $room->status == 4 && $room->close_room) {
            $pay_gain = 1;
            $end = 1;
        }
        if ($pay_gain == 0) {
            if ($room->close_room) {
                if ($room->close_room - time() <= 0) {
                    if ($pj_bg_all > 0) {
                        $walletADM = Wallet::where("usuario_id", 1)->first();
                        $user_win = BingoUserRoom::where('room', $id)
                            ->where('user', $user)->where('bg_all', 1)->first();
                        if ($room->status == 3) {
                            DB::table('bingo_room')->where('id', $id)->update(['bg_all' => 1, 'status' => 4]);
                        }
                        if (!empty($user_win) && $user_win->amount == 0) {
                            $pz_songs = 0;
                            if ($user_win->prize_sm > 0) {
                                $pz_songs = $room->val_songs * $user_win->prize_sm;
                            }
                            $gain = ($bot - $comision + $pz_songs) / $pj_bg_all;
                            $wallet = Wallet::where("usuario_id", $user_win->user)->first();
                            if ($room->coin != 1) {
                                Wallet::where("usuario_id", $user_win->user)
                                    ->update(['saldo' => $wallet->saldo + $gain]);
                                Wallet::where("usuario_id", 1)
                                    ->update(['saldo' => $walletADM->saldo + $comision]);
                            } else {
                                Wallet::where("usuario_id", $user_win->user)
                                    ->update(['usdt' => $wallet->usdt + $gain]);
                                Wallet::where("usuario_id", 1)
                                    ->update(['usdt' => $walletADM->usdt + $comision]);
                            }
                            DB::table('bingo_room_user')->where('user', $user_win->user)
                                ->where('room', $id)->update(['paid' => 1]);

                            HistGame::where('game', 1)->where('room', $id)
                                ->where('user', $user_win->user)->update(['gain' => $gain]);
                        }
                        $end = 1;
                    } else {
                        $end = 1; /// Aplicar este cambio en BingoCash
                    }
                } else {
                    $end = 0;
                }
            } else {
                $end = 0;
                if ($srt[74]->time != null && $srt[74]->time + 6 < time()) {
                    $end = 1;
                    DB::table('bingo_room')->where('id', $id)->update(['status' => 4, 'close_room' => time()]);
                }
            }
        }
        $info_user = ['name' => [], 'foto' => [], 'gain' => [], 'hour' => [], 'ctns' => []];
        $info_nm = DB::table("bingo_room_user")
            ->join('users', 'users.id', 'bingo_room_user.user')
            ->where('room', $id)->where('winner', 1)->orderByDesc('time_prize')->get();

        if ($pj_bg_all > 0) {
            $pm_wins = $pj_bg_all;
            foreach ($info_nm as $key => $value) {
                if ($value->prize_sm == 1 || $value->bg_all == 1) {
                    $exp = 1;
                    if ($room->coin == 2) {
                        $CommerceExchangeRate = DB::table("CommerceExchangeRates")->orderByDesc('created_at')->first();
                        $exp = $CommerceExchangeRate->CommerceExchangeRate_RKE;
                    }
                    $gain_total = ($room->val_songs * $exp) * $value->prize_sm;
                    if ($value->bg_all == 1) {
                        $gain_total += (($bot - $comision) / $pm_wins);
                    }
                    $info_user['name'][] = $value->username;
                    $info_user['foto'][] = $value->foto;
                    $info_user['gain'][] = $gain_total;
                    $info_user['hour'][] = $value->time_prize;
                    $info_user['ctns'][] = $value->ctn_win;
                }
            }
            $end = 1;
        } else {
            $lastChip = DB::table("bingo_sort")->where('time', '!=', null)->where('room', $id)->latest('id')->first();
            if ($srt[74]->chip != $lastChip->chip) {
                $van_sort = $this->van_sort($id);
                $chip_vsrt = DB::table("bingo_sort")->where('chip', $van_sort->chip)
                    ->where('room', $id)->where('time', '<=', time())->first();
                if ($chip_vsrt) {
                    $nw_chip = [];
                    if ($lastChip->time <= time() && in_array($lastChip->chip_win, [null, 'VAN'])) {
                        $nw_dtm = $lastChip->time + 10;
                        $new_chp = DB::table("bingo_sort")->where('id', $lastChip->id + 1)->first();
                        if ($new_chp) {
                            DB::table("bingo_sort")->where('id', $new_chp->id)->update(['time' => $nw_dtm]);
                            $nw_chip = [$new_chp->chip, $nw_dtm];
                        }
                    } else {
                        $nw_chip = [$lastChip->chip, $lastChip->time];
                    }
                    if ($room->close_room == null) {
                        $end = 0;
                    }
                }
            }
        }
        if ($end == 1) {
            if ($room->replay == 1) {
                $boat = 0;
                if ($room->type_boat == 2) {
                    $boat = $room->jackpot;
                }
                $mod_date = strtotime($room->date . "+ 1 day");
                BingoRoom::create([
                    'date' => date('Y-m-d', $mod_date),
                    'time' => $room->time,
                    'replay' => 1,
                    'coin' => $room->coin,
                    'price' => $room->price,
                    'val_automc' => $room->val_automc,
                    'type_boat' => $room->type_boat,
                    'jackpot' => $boat,
                    'com' => $room->com,
                    'status' => 2,
                ]);
            }
        }

        $wnchip = $this->chip_win($id);
        echo json_encode(array(
            'end' => $end, 'winns' => $info_user, 'timeServ' => time(),
            'nextChip' => (!empty($nw_chip)) ? $nw_chip : [], 'chipWin' => $wnchip
        ));
    }

    public function viewCtn($ctnId)
    {
        $ctn = BingoCtns::with('User')->where('id', $ctnId)->first();

        if ($ctn) {
            $allCtn = [
                $ctn->b1, $ctn->b2, $ctn->b3, $ctn->b4, $ctn->b5,
                $ctn->i1, $ctn->i2, $ctn->i3, $ctn->i4, $ctn->i5,
                $ctn->n1, $ctn->n2, $ctn->n3, $ctn->n4,
                $ctn->g1, $ctn->g2, $ctn->g3, $ctn->g4, $ctn->g5,
                $ctn->o1, $ctn->o2, $ctn->o3, $ctn->o4, $ctn->o5
            ];

            $playerName = $ctn->User->username;

            return ['ctn' => $allCtn, 'playerName' => $playerName];
        }
        return null;
    }
}
