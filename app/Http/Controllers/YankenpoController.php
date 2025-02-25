<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class YankenpoController extends Controller
{
    public function rooms()
    {
        $user = auth()->user()->id;
        
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();

        if (auth()->user()->user_type == 2) {
            return view('modules/admin/yankenpo/rooms')->with([
                'wallet' => $wallet,
            ]);
        } elseif ((auth()->user()->user_type == 1)) {
            return view('modules/user/yankenpo/rooms')->with([]);
        }
    }

    public function debit_publish_room(Request $request)
    {
        ///Funcion para debitar, publicar sala de apuesta
        $user = auth()->user()->id;
        $type_bet = $request->tp_bet;
        $room_user = DB::table("ppt_pvp_bets")
            ->leftjoin("ppuser_type", 'ppuser_type.room_id', 'ppt_pvp_bets.room_id')
            ->where('user', $user)->orderByDesc('ppuser_type.id')->first();
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();

        if ($room_user->status == 4) {
            if ($type_bet == 1) {
                ///Publicar nueva Sala de Juego Versus
                DB::table("ppt_pvp_bets")->insert([]);
            } else if ($type_bet == 2) {
                ///Buscador de Salas
            } else if ($type_bet == 3) {
                ///Jugada contra la maquina Robot
                DB::table("ppt_pvp_bets")->insert([]);
            }
        }
    }

    public function check_opponent(Request $request)
    {
        $opponent = DB::table("ppuser_type")->where('room_id', $request->room)
            ->where('user', "!=", auth()->user()->id)->first();
        return $opponent;
    }


    public function game($id)
    {
        $user = auth()->user()->id;
        $room = DB::table("ppt_pvp_bets")->leftjoin("ppuser_type", 'ppuser_type.room_id', 'ppt_pvp_bets.room_id')
            ->where("status", 3)->where('ppt_pvp_bets.room_id', $id)->where('user', $user)->first();
        if ($room == null) {
            return redirect()->back();
        }
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $name_user = DB::table("ppuser_type")->leftJoin('users', 'users.id', 'ppuser_type.user')
            ->where('ppt_pvp_bets.room_id', $id)->select('ppuser_type.user', 'users.username')->get();
            
        return view('modules/user/yankenpo/game')->with([
            'wallet' => $wallet,
            'room' => $room,
            'name_user' => $name_user
        ]);
    }

    public function select_ppt(Request $request)
    {
        $user = auth()->user()->id;
        $room = $request->room;
        $ppt = $request->ppt;
        $user_round = DB::table("ppuser_type")->where('room_id', $room)
            ->where('user', $user)->orderByDesc('id')->get();
        if ($ppt != 4) {
            if (count($user_round) < 3 && $user_round[0]->ppt == null) {
                DB::table("ppuser_type")->where('room_id', $room)
                    ->where('user', $user)->update([
                        'ppt' => $ppt, 'round' => count($user_round)
                    ]);
            }
            echo json_encode($ppt);
        } else {
            DB::table("ppuser_type")->where('room_id', $room)
                ->where('user', $user)->update(['ppt' => null]);
            echo json_encode(4); // Eliminar Eleccion
        }
    }

    public function check_ppt($id)
    {
        $room = DB::table("ppt_pvp_bets")->where('room_id', $id)->first();
        $player_one = DB::table("ppuser_type")->where('user', $room->player_one)
            ->orderByDesc('id')->where('room_id', $id)->first();
        $round = $player_one->round;
        $player_two = DB::table("ppuser_type")->where('user', $room->player_two)
            ->where('room_id', $id)->where('round', $round)->first();

        if ($player_one->ppt == 1) {
            ///ppt == 1 Piedra
            if ($player_two->ppt == 2) {
                $this->process_ppt($id, $round, $player_two->user);
            } else if ($player_two->ppt == $player_one->ppt) {
                echo json_encode(array('van' => 0));
            } else {
                $this->process_ppt($id, $round, $player_one->user);
            }
        } elseif ($player_one->ppt == 2) {
            ///ppt == 2 Papel
            if ($player_two->ppt == 3) {
                $this->process_ppt($id, $round, $player_two->user);
            } else if ($player_two->ppt == $player_one->ppt) {
                echo json_encode(array('van' => 0)); ///Van 0 Empate
            } else {
                $this->process_ppt($id, $round, $player_one->user);
            }
        } elseif ($player_one->ppt == 3) {
            ///ppt == 3 Tijera
            if ($player_two->ppt == 1) {
                $this->process_ppt($id, $round, $player_two->user);
            } else if ($player_two->ppt == $player_one->ppt) {
                echo json_encode(array('van' => 0));
            } else {
                $this->process_ppt($id, $round, $player_one->user);
            }
        }
    }

    public function process_ppt($rm_id, $round, $user)
    {
        $ppuser_type_win = DB::table("ppuser_type")->where('user', $user)
            ->where('room_id', $rm_id)->where('round', $round)->first();

        if ($ppuser_type_win->win == null) {
            DB::table("ppuser_type")->where('user', $user)->where('room_id', $rm_id)
                ->where('round', $round)->update(['win' => 1]);
            $user_opponent = DB::table("ppuser_type")->where('user', '!=', $user)
                ->where('room_id', $rm_id)->where('round', $round)->first();
            DB::table("ppuser_type")->where('user', $user_opponent->user)->where('room_id', $rm_id)
                ->where('round', $round)->update(['win' => 0]);

            $players = [$user, $user_opponent->user];
            foreach ($players as $key => $value) {
                DB::table("ppuser_type")->insert([
                    'room_id' => $rm_id, 'user' => $value, 'ppt' => null, 'round' => $round + 1
                ]);
            }
        }
        $ppuser_type_win = DB::table("ppuser_type")->where('user', auth()->user()->id)
            ->where('room_id', $rm_id)->where('round', $round)->first();

        echo json_encode(array('rnd_vn' => $ppuser_type_win->win));
        ///Retorna si el user en session gana o pierde
    }
}
