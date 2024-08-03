<?php

namespace App\Http\Controllers;

use App\Models\CommerceExchangeRate;
use App\Models\Games;
use App\Models\Coin;
use App\Models\TriviaMulti;
use App\Models\Wallet;
use App\Models\question;
use App\Models\TriviaIndv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\ExtrasUser;
use App\Models\GameExtras;
use App\Models\GameSounds;
use App\Models\Ghosts;
use App\Models\HistGame;
use App\Models\TriviaMultiCups;
use App\Models\TriviaMultiQuest;
use App\Traits\UserVal;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Session;
use DateTime;

class TriviaController extends Controller
{
    use UserVal;

    public function __construct()
    {
        parent::__construct();
    }

    ////////////////////////// Admin
    public function edit_sound()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->join('monedas', 'monedas.id', 'Wallets.coin')->first();
        return view('modules/admin/trivia/sound')->with([
            'wallet' => $wallet
        ]);
    }

    public function store_sound(Request $request)
    {
        if (auth()->user()->user_type == 1) {
            return redirect()->back();
        }
        $sounds = DB::table('audio_tri')->where('id', 1)->first();
        $track = $request->file('track');
        $name_track = $track->getClientOriginalName();
        $track->move('sounds/trivia/', $name_track);

        $s_correct = $request->file('s_correct');
        $name_scorrect = $s_correct->getClientOriginalName();
        $s_correct->move('sounds/trivia/', $name_scorrect);

        $s_incorrect = $request->file('s_incorrect');
        $name_sincorrect = $s_incorrect->getClientOriginalName();
        $s_incorrect->move('sounds/trivia/', $name_sincorrect);

        if ($sounds->id != 1) {
            DB::table('audio_tri')->insertGetId([
                'track' => $name_track, 'correct' => $name_scorrect,
                'incorrect' => $name_sincorrect
            ]);
        } else {
            DB::table('audio_tri')->update([
                'track' => $name_track, 'correct' => $name_scorrect,
                'incorrect' => $name_sincorrect
            ]);
        }

        Session::flash('mensaje', 'Sonidos cargados correctamente.');
        return redirect()->route('trivia.index');
    }

    public function create()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->join('monedas', 'monedas.id', 'Wallets.coin')->first();
        $coins = DB::table('monedas')->get();
        return view('modules/admin/trivia/create')->with(['wallet' => $wallet, 'coins' => $coins]);
    }

    public function store(Request $request)
    {
        $files = $request->file('imagen');
        if (empty($files)) {
            $nombreimg = '';
        } else {
            $nombreimg = $files->getClientOriginalName();
            $ruta = public_path('imgSystem/imgTrivia/' . $nombreimg);
            Image::make($request->file('imagen'))
                ->resize(320, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($ruta);
        }
        TriviaMulti::create([
            'status' => 1,
            'nombre' => $request->nombre,
            'imagen' => $nombreimg,
            'fecha' => $request->fecha,
            'hora' => $request->hora,
            'coin' => $request->moneda,
            'precio' => $request->precio,
            'min_pj' => $request->min_pj,
            'type_boat' => 1,
            'com' => $request->comision,
            'replay' => 0
        ]);
        Session::flash('mensaje', 'Trivia creada correctamente.');
        return redirect()->route('trivia.rooms');
    }

    //TRIVIA INDIVIDUAL

    public function create_quest_indv()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->join('monedas', 'monedas.id', 'Wallets.coin')->first();
        $questions = DB::table('triv_indv_quest')->get();
        return view('modules/admin/trivia/create_indv')->with([
            'wallet' => $wallet,
            'question' => $questions,
            'CommerceExchangeRate' => $this->ts()
        ]);
    }

    public function insert_quest_indv(Request $request)
    {
        $files = $request->file('imagen');
        $nombreimg = $files->getClientOriginalName();
        $ruta = public_path('imgSystem/imgTrivia/' . $nombreimg);

        Image::make($request->file('imagen'))
            ->resize(320, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save($ruta);

        $data = [
            'question' => $request->quest,
            'resp_correct' => $request->correct,
            'opc_1' => $request->opc_1,
            'opc_2' => $request->opc_2,
            'opc_3' => $request->opc_3,
            'img' => $nombreimg
        ];

        TriviaIndv::create($data);
        Session::flash('mensaje', 'Proceso exitoso');
        return redirect()->route('triv_in.create_quest');
    }

    public function edit_quest_indv(Request $request)
    {
        if ($request->imgQ_e != null) {
            $files = $request->file('imgQ_e');
            $nombreimg = $files->getClientOriginalName();
            $ruta = public_path('imgSystem/imgTrivia/' . $nombreimg);

            Image::make($request->file('imgQ_e'))
                ->resize(320, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($ruta);
        }

        DB::table('triv_indv_quest')->where('id', $request->id_p)->update(
            [
                'question' => $request->quest_e,
                'resp_correct' => $request->correct,
                'opc_1' => $request->opc_1e,
                'opc_2' => $request->opc_2e,
                'opc_3' => $request->opc_3e,
                'img' => $nombreimg
            ]
        );

        Session::flash('mensaje', 'Proceso Efectuado Con Exito');
        return redirect()->back();
    }
    ////////////////////////// Admin

    public function QuestSendTelegram($room) //Consultar Proxima respuesta a enviar en base al tiempo
    {
        $triv = TriviaMulti::where('id', $room)->first();

        $triv_signs = DB::table('triv_multi_signs')
            ->where('room', $room)->orderByDesc('num_quest')->first();

        $van_time_run = $triv_signs->num_quest * 13;
        date_default_timezone_set('America/Caracas');
        $hour_serv = strtotime(date('Y-m-d H:i'));
        $hour_count = strtotime('+' . $van_time_run . 'second', strtotime($triv->fecha . ' ' . $triv->hora));

        if ($hour_count < $hour_serv && $triv_signs->num_quest <= 9) {
            $this->SendTelegram($room, $triv_signs->num_quest + 1);
        }
    }

    public function SendTelegram($room, $num_quest)
    {
        // TriviaMulti::update(['signs' => 1]);

        $triv_user = DB::table('triv_multi_user')->where('id', $room)
            ->where('lose_win', 0)->where('winner', 0)->count();

        $triv = DB::table('triv_multi')
            ->join('monedas', 'monedas.id', 'triv_multi.coin')
            ->join('triv_multi_quest', 'triv_multi_quest.room', 'triv_multi.id')
            ->where('room', $room)->where('num_quest', $num_quest)->first();

        define('TELEGRAM_BOT_TOKEN', '6728305116:AAFHjVrT6D8YS2iI7mRjbxm8SBadLRERVC0');
        define('TELEGRAM_API_URL', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/');

        function isValidToken($token)
        {
            // Compara el token proporcionado con el token configurado
            return $token === TELEGRAM_BOT_TOKEN;
        }

        function isValidChatId($chatId)
        {
            // Puedes implementar lógica adicional para validar el ID del chat según tus necesidades
            return preg_match('/^-?\d+$/', $chatId) === 1;
        }

        function isValidMessageText($text)
        {
            // Puedes implementar lógica adicional para validar el texto del mensaje según tus necesidades
            return strlen($text) > 0;
        }

        function sendMessage($chatId, $text)
        {
            if (!isValidChatId($chatId) || !isValidMessageText($text)) {
                // Datos no válidos, puedes devolver un error o simplemente salir
                die('Datos de entrada no válidos');
            }

            $url = TELEGRAM_API_URL . 'sendMessage';
            $formattedText = str_replace('<br>', "\n", $text);

            $params = [
                'chat_id' => $chatId,
                'text' => $formattedText,
                'parse_mode' => 'HTML',
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Configurar para aceptar conexiones seguras
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            $result = curl_exec($ch);

            if (curl_errno($ch)) {
                // Manejar error
                echo 'Error: ' . curl_error($ch);
            }

            curl_close($ch);

            return json_decode($result, true);
        }

        // Validar el token antes de realizar cualquier acción
        $providedToken = '6728305116:AAFHjVrT6D8YS2iI7mRjbxm8SBadLRERVC0'; // Asegúrate de cambiar a POST si es más adecuado para tu caso
        if (!isValidToken($providedToken)) {
            // Token no válido, puedes devolver un error o simplemente salir
            die('Acceso no autorizado');
        }

        // Ejemplo de uso
        $fecha = new DateTime($triv->fecha);
        $fechaFormt = $fecha->format("d/m/y");
        $providedChatId = '-1002056848129'; // Asegúrate de cambiar a POST si es más adecuado para tu caso
        $arrowEmojiRG = '➡️';
        $iconCheck = '✅';
        $providedMessageText =
            'QUEST #' . $num_quest . '<br>' .
            '<b>¿' . $triv->question . '?</b>' . '<br>' .
            $iconCheck . ' ' . $triv->correct_answer . '<br>' .
            '<br><b>FECHA: </b> ' . $fechaFormt .
            '<br><b>HORA: </b> ' . $triv->hora . ' UTC-4' .
            '<br><b>jackpot: </b> ' . $triv->jackpot . ' ' . $triv->name;

        if (!isValidChatId($providedChatId) || !isValidMessageText($providedMessageText)) {
            // Datos no válidos, puedes devolver un error o simplemente salir
            die('Datos de entrada no válidos');
        }

        // Ejemplo de uso
        $chatId = $providedChatId;
        $messageText = $providedMessageText;

        $response = sendMessage($chatId, $messageText);

        $triv_signs = DB::table('triv_multi_signs')
            ->where('room', $room)->where('num_quest', $num_quest)->first();

        if ($response && $response['ok']) {
            if ($triv_signs == null) {
                DB::table('triv_multi_signs')->insert([
                    'room' => $room, 'num_quest' => $num_quest
                ]);
                TriviaMulti::where('room', $room)->update(['signs' => 0]);
            }
            // echo 'Mensaje enviado correctamente.';
        }
    }

    public function allRooms()
    {
        $user = $this->user();
        if ($user->user_type == 2) {
            $wallet = $this->Wallet($user->id);
            $title = 'Salas de Bingo';
            $trivia = TriviaMulti::with(['Coin', 'Status'])->orderBy('created_at')->get();

            return view('modules.admin.trivia.rooms', compact('wallet', 'title', 'trivias'));
        }
    }

    public function roomshh($symCoin)
    {
        $user = $this->user();
        $wallet = $this->Wallet($user->id);
        $coin = 0;
        if (!$symCoin == 'USDT')  $coin = $wallet->Coin->id;

        $this->changeStatusRoom('');

        $triv_multi = DB::table("triv_multi")->leftjoin(
            "triv_multi_user",
            function ($join) {
                $join->on('triv_multi_user.room', 'triv_multi.id')->where("user", auth()->user()->id);
            }
        )->select(
            'triv_multi.id',
            'triv_multi.status',
            'triv_multi_user.user',
            'triv_multi.fecha',
            'triv_multi.hora',
            'triv_multi.coin',
            'triv_multi.precio',
            'triv_multi.jackpot',
            'triv_multi.com',
            'triv_multi.type_boat',
            'triv_multi.imagen'
        )->whereIn("status", [2, 3])->where('coin', 1)->get();

        $tri_pvp = DB::table('triv_pvp_cdin')->where('id_user', $user)->first();
        $tri_ndv = DB::table('triv_indv_cdin')->where('id_user', $user)->first();
        $quser_type = DB::table('triv_indv_user')->where('id_user', $user)
            ->where('lose_win', '!=', null)->pluck('id_quest')->toArray();
        $triv_indv = DB::table('triv_indv_quest')->pluck('id')->toArray();
        $arr_quest = [];
        if (count($triv_indv) > 0) {
            for ($i = 0; $i < 10; $i++) {
                $rnd = $triv_indv[array_rand($triv_indv)];
                if (in_array($rnd, $quser_type)) {
                    continue;
                } else {
                    if (in_array($rnd, $arr_quest)) {
                        continue;
                    } else {
                        array_push($arr_quest, $rnd);
                    }
                }
                $i = 0;
            }
        }
        return view('modules/user/trivia/rooms')->with([
            'user' => $user,
            'wallet' => $wallet,
            'date_serv' => date('Y-m-d H:i'),
            'triv_multi' => $triv_multi,
            'tri_ndv' => $tri_ndv,
            'tri_pvp' => $tri_pvp,
            'quser_type' => count($arr_quest)
        ]);
    }
    public function rooms($symCoin)
    {
        $user = $this->user();
        $wallet = $this->Wallet($user->id);
        $coinRoom = $symCoin;
        $coin = strtoupper($symCoin) == 'USDT' ? 1 : $wallet->Coin->id;

        // $this->changeStatusRoom('');

        $trivMulti = TriviaMulti::with(['PlayersRoom' => function ($query) use ($user) {
            $query->where('user', $user->id);
        }])->select([
            'id', 'status', 'date', 'time', 'coin',
            'price', 'players', 'type_boat', 'jackpot', 'imagen'
        ])->whereIn('status', [2, 3])->where('coin', $coin)->get();

        $date = now()->toDateTimeString();
        return view('modules.user.trivia.rooms', compact('user', 'wallet', 'date', 'coin', 'trivMulti', 'coinRoom'));
    }


    public function timer($id)
    {
        return TriviaMulti::select('id', 'status', 'fecha', 'hora')
            ->where('coin', $id)->whereIn('status', [2, 3])->get();
    }

    public function changeStatusRoom($room)
    {
        date_default_timezone_set('America/Caracas');
        $arrEndRoom = [];
        $roomsAlone = DB::table("triv_multi")->whereIn('status', [2, 3])->get();
        $triviaUser = DB::table('triv_multi_user')->where('room', $room)->where('lose_win', 1)->count();
        foreach ($roomsAlone as $key => $value) {
            $dataTime = strtotime($value->fecha . $value->hora);
            if ($dataTime <= time() && $value->players == 0) {
                TriviaMulti::where('id', $value->id)->update(['status' => 4]);
                array_push($arrEndRoom, $value->id);
            }
            if ($dataTime + 375 < time() && $triviaUser == 0) {
                TriviaMulti::where('id', $value->id)->update(['status' => 6]);
                array_push($arrEndRoom, $value->id);
            }
        }

        if ($room) {
            $trivia = TriviaMulti::where('id', $room)->first();
            $vanRoom = 0;
            if (strtotime($trivia->fecha . $trivia->hora) < time() && $trivia->status != 4) {
                if ($trivia->status == 2) {
                    TriviaMulti::where('id', $room)->update(['status' => 3]);
                } else {
                    $triv_user = DB::table('triv_multi_user')->where('room', $room)
                        ->where('user', auth()->user()->id)->first();
                    if ($triv_user) $vanRoom = 1;
                }
            }
            echo json_encode(array('van' => $vanRoom, 'rfs' => $arrEndRoom));
        }
    }

    public function roomMulti($symCoin, $room) //Multijugador
    {
        $coin = Coin::where('name', $symCoin)->first();
        if (!$coin) return redirect()->back();

        $user = $this->user();
        $wallet = $this->Wallet($user->id);

        $game = TriviaMulti::with(['PlayersRoom'])
            ->whereHas('PlayersRoom', function ($query) use ($user) {
                $query->where('user', $user->id);
            })->where('id', $room)->where('status', 3)->first();

        if (!$game) return redirect()->back();

        $cupsQuest = TriviaMultiCups::where('room', $room)->get();

        if (!count($cupsQuest)) {
            $cupsQuest = $this->getCupsRoom($room);
        }
        $infoGame = $this->infoGame($room);

        // $van_time = 0;
        // if ($trivia->fin_qt == "") {
        //     $contadornum = 25;
        // } else {
        //     $van_time = 1;
        //     $contadornum = $trivia->fin_qt - time();
        // }
        // if (count($cups) > 0 && $van_time == 1) {
        //     if ($cups[9]->cups == 0) {
        //         $this->all_winners($id);
        //     }
        // }

        // $dsc_tv = DB::table('discount_triv')->where('usuario_id', $user)->first();
        // if ($dsc_tv == null) {
        //     $dsc_tv = 0;
        // } else {
        //     $dsc_tv = 1;
        // }

        // $send_wns = DB::table('wn_ghosts')->where('game', 2)->where('id_game', $id)->first();

        $rank = $this->posRank($room);
        $extrasGame = GameExtras::whereIn('id_game', [2, 0])->where('status', 1)->get();
        $extrasUser = ExtrasUser::where('user', $user)->first();
        $sounds = GameSounds::whereIn('id_game', [2, 0])->get();

        return view(
            'modules.user.trivia.rm_multi',
            compact('wallet', 'game', 'cupsQuest', 'extrasUser', 'infoGame')
        );
        // 'sound_trv' => $sounds,
        // 'tm_serv' => $tm_serv,
        // 'send_wns' => $send_wns,
        // 'lives' => $ext->lives,
        // 'count_players' => $this->count_players($id),
        // 'count_cups' => $cups->cups,
        // 'count_winners' => $this->count_winners($id),
        // 'question' => $questions['qtions'],
        // 'answers' => $questions['respuesta'],
        // 'xyz_w' => $questions['respt_inc'],
        // 'numpp' => $questions['numpp'],
        // 'idpr' => $questions['idpr'],
        // 'img' => $questions['img'],
        // 'b_life' => $b_life->price,
        // 'rank' => $r_pos,
        // 'dist' => $dsc_tv,
        // 'end_gm' => $trivia->end_gm - $trivia->init_gm,
        // 'contador' => $contadornum,
        // 'van_time' => $van_time
    }

    public function room_indv() //Bolivares
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->join('monedas', 'monedas.id', 'Wallets.coin')->first();
        $sounds = DB::table('audio_tri')->get();
        $cdin = DB::table('triv_indv_cdin')->where('id_user', $user)
            ->orderBy('id', 'desc')->first();
        if ($cdin == null || $cdin->start == 2) {
            return redirect()->route('trivia.index');
        }
        $quest = $this->rand_quest($cdin->id);
        if ($cdin->start == 0) {
            $triv = DB::table('triv_indv_user')->where('id_user', $user)
                ->where('id_triv', $cdin->id)->where('lose_win', null)->first();
        } else {
            $triv = DB::table('triv_indv_user')->where('id_user', $user)
                ->where('id_triv', $cdin->id)->where('lose_win', '!=', null)
                ->orderBy('nm_quest', 'desc')->first();
        }
        $spy = DB::table('triv_indv_user')->where('id_user', $user)
            ->where('id_triv', $cdin->id)->first();
        if ($spy->init_spy == null) {
            $cnt_spy = 7;
        } else {
            $cnt_spy = $triv->init_spy;
        }
        if ($spy->mdl_retire == 0) {
            $tm_ret = 6;
        } else {
            $tm_ret = $spy->mdl_retire;
        }
        $sz_qst = DB::table('triv_indv_user')->where('id_user', $user)
            ->where('id_triv', $cdin->id)->orderBy('nm_quest', 'desc')->first();
        $time_gm = $cdin->end_triv - $cdin->init_triv;
        $dsc_tv = DB::table('discount_triv')->where('usuario_id', $user)->first();

        return view('modules/user/trivia/rm_indv')->with([
            'wallet' => $wallet,
            'CommerceExchangeRate' => $this->ts(),
            'sound_trv' => $sounds,
            'gain' => $triv->multi * $cdin->bet,
            'c_half' => $cdin->c_half,
            'use_half' => $cdin->use_half,
            'id_triv' => $triv->id_triv,
            's_live' => $triv->lose_win,
            'quest' => $quest['questions'],
            'answers' => $quest['answers'],
            'img' => $quest['img'],
            'tim_end' => $triv->end_quest - time(),
            'xyz' => $quest['respt_inc'],
            'start' => $cdin->start,
            'md_retire' => $cdin->mdl_retire,
            'tm_ret' => $tm_ret,
            'cnt_spy' => $cnt_spy,
            'nm_quest' => $triv->nm_quest,
            'sz_qst' => $sz_qst->nm_quest,
            'time_gm' => $time_gm,
            'desct' => $dsc_tv,
        ]);
    }

    public function rand_quest($id)
    {
        $user = auth()->user()->id;
        $cdin = DB::table('triv_indv_cdin')->where('id_user', $user)->where('id', $id)->first();
        if ($cdin->start == null) {
            $nm_qt = DB::table('triv_indv_user')->where('id_user', $user)
                ->where('id_triv', $id)->first();
        } else {
            $triv = DB::table('triv_indv_user')->where('id_user', $user)
                ->where('id_triv', $cdin->id)->first();
            if ($triv->lose_win == null) {
                DB::table('triv_indv_user')->where('id_triv', $cdin->id)
                    ->where('nm_quest', 1)->update(['lose_win' => 0]);
            }
            $nm_qt = DB::table('triv_indv_user')->where('id_user', $user)
                ->where('id_triv', $id)->where('lose_win', '!=', null)
                ->orderBy('nm_quest', 'desc')->first();
        }

        $questions = DB::table('triv_indv_quest')->where('id', $nm_qt->id_quest)->first();
        $array = [];
        $ban =  $rda = rand(0, 3);
        $ban2 = '';
        $ban3 = '';

        $array[$rda] = $questions->resp_correct;
        for ($i = 0; $i < 5; $i++) {
            $rda = rand(0, 3);
            if ($ban <> $rda) {
                $ban2 = $rda;
                break;
            }
            $i = 0;
        }
        $array[$rda] = $questions->opc_1;
        for ($i = 0; $i < 5; $i++) {
            $rda = rand(0, 3);
            if ($ban <> $rda && $ban2 <> $rda) {
                $ban3 = $rda;
                break;
            }
            $i = 0;
        }
        $array[$rda] = $questions->opc_2;
        for ($i = 0; $i < 5; $i++) {
            $rda = rand(0, 3);
            if ($ban <> $rda && $ban2 <> $rda && $ban3 <> $rda) {
                break;
            }
            $i = 0;
        }
        $array[$rda] = $questions->opc_3;

        //50/50
        $ar_half = [];
        $van = $rda_p = rand(0, 2);
        $van2 = '';

        $ar_half[$rda_p] = $questions->opc_1;
        for ($i = 0; $i < 4; $i++) {
            $rda_p = rand(0, 2);
            if ($van <> $rda_p) {
                $van2 = $rda_p;
                break;
            }
            $i = 0;
        }
        $ar_half[$rda_p] = $questions->opc_2;
        for ($i = 0; $i < 4; $i++) {
            $rda_p = rand(0, 2);
            if ($van <> $rda_p && $van2 <> $rda_p) {
                break;
            }
            $i = 0;
        }
        $ar_half[$rda_p] = $questions->opc_3;
        return array(
            'respt_inc' => $ar_half, 'nm_quest' => $nm_qt->nm_quest,
            'questions' => $questions->question, 'answers' => $array,
            'img' => $questions->img
        );
    }

    public function spy_triv_indv(Request $request)
    {
        $cdin = DB::table('triv_indv_cdin')->where('id_user', auth()->user()->id)
            ->orderBy('id', 'desc')->first();

        DB::table('triv_indv_user')->where('id_triv', $cdin->id)
            ->where('nm_quest', $request->nm_quest)->update(['init_spy' => time()]);
    }

    public function start_triv_indv(Request $request)
    {
        $user = auth()->user()->id;
        if ($request->init == 0) {
            DB::table('triv_indv_cdin')->where('id_user', $user)
                ->orderBy('id', 'desc')->update(['init_triv' => time(), 'start' => 1]);
        }
        date_default_timezone_set('America/Caracas');
        $round = DB::table('triv_indv_user')->where('id_user', $user)
            ->where('lose_win', null)->first();
        DB::table('triv_indv_user')->where('id', $round->id)
            ->update(['init_quest' => time(), 'lose_win' => 0, 'end_quest' => time() + 20]);
        echo json_encode(array('van' => 1, 'profit' => 0));
    }

    public function end_time_triv_indv(Request $request)
    {
        $user = auth()->user()->id;
        $cdin = DB::table('triv_indv_cdin')->where('id_user', auth()->user()->id)
            ->orderBy('id', 'desc')->first();

        DB::table('triv_indv_cdin')->where('id_user', $user)->orderBy('id', 'desc')
            ->update(['start' => 2, 'c_half' => 2, 'use_half' => 0]);
        $wallet_admin = Wallet::where('usuario_id', 1)->first();
        Wallet::where("usuario_id", 1)->update(['saldo' => $wallet_admin->saldo + $cdin->bet]);
        DB::table('triv_indv_user')->where('id_triv', $cdin->id)
            ->where('nm_quest', $request->nm_quest)->update(['lose_win' => 1]);
        $this->clear_quest($cdin->id);
        echo json_encode(array('van' => 0, 'profit' => 0));
    }

    public function c_half_indv()
    {
        DB::table('triv_indv_cdin')->where('id_user', auth()->user()->id)
            ->orderBy('id', 'desc')->update(['use_half' => 1]);
        echo json_encode(array('van' => 3));
    }

    public function anwers_triv_indv(Request $request)
    {
        $user = auth()->user()->id;
        $triv = DB::table('triv_indv_user')->where('id_user', $user)
            ->where('id_triv', $request->id_triv)->where('nm_quest', $request->nm_quest)->first();
        $cdin = DB::table('triv_indv_cdin')->where('id_user', $user)
            ->orderBy('id', 'desc')->first();
        $quest = DB::table('triv_indv_quest')->where('id', $triv->id_quest)->first();

        if ($quest->resp_correct == $request->resp) {
            if ($request->nm_quest < 8) {
                DB::table('triv_indv_cdin')->where('id_user', $user)
                    ->orderBy('id', 'desc')->update(['mdl_retire' => time()]);
                echo json_encode(array('van' => 1, 'profit' => $triv->multi * $cdin->bet));
            } else {
                $wallet = DB::table('Wallets')->where('usuario_id', $user)->first();
                DB::table('triv_indv_cdin')->where('id_user', $user)->orderBy('id', 'desc')
                    ->update(['start' => 2, 'end_triv' => time()]);
                DB::table('Wallets')->where('usuario_id', $user)
                    ->update(['saldo' => $wallet->saldo + $triv->multi * $cdin->bet]);
                DB::table('triv_indv_user')->where('id_triv', $cdin->id)
                    ->where('nm_quest', $request->nm_quest)->update(['lose_win' => 3]);
                $this->clear_quest($cdin->id);
                echo json_encode(array('van' => 2, 'profit' => $triv->multi * $cdin->bet));
            }
        } else {
            $wallet_admin = Wallet::where('usuario_id', 1)->first();
            Wallet::where("usuario_id", 1)->update(['saldo' => $wallet_admin->saldo + $cdin->bet]);
            DB::table('triv_indv_user')->where('id_triv', $cdin->id)
                ->where('nm_quest', $request->nm_quest)->update(['lose_win' => 2]);
            DB::table('triv_indv_cdin')->where('id_user', $user)->orderBy('id', 'desc')
                ->update(['start' => 2]);
            echo json_encode(array('van' => 0, 'profit' => 0));
        }
    }

    public function insert_next_question($id)
    {
        $user = auth()->user()->id;
        $triv = DB::table('triv_indv_user')->where('id_user', $user)
            ->where('lose_win', 0)->orderBy('nm_quest', 'DESC')->first();
        $quest = DB::table('triv_indv_quest')->where('id', $triv->id_quest)->first();

        DB::table('triv_indv_user')->where('id_user', $user)->where('id_triv', $id)
            ->where('nm_quest', $triv->nm_quest)->update(['lose_win' => 1]);

        DB::table('triv_indv_user')->where('id_user', $user)->where('id_triv', $id)
            ->where('nm_quest', $triv->nm_quest + 1)
            ->update(['init_quest' => time(), 'lose_win' => 0, 'end_quest' => time() + 20]);

        DB::table('triv_indv_cdin')->where('id_user', $user)
            ->orderBy('id', 'desc')->update(['mdl_retire' => 0]);
        $quest = $this->rand_quest($id);
        echo json_encode(array(
            'van' => 1, 'xyz_w' => $quest['respt_inc'], 'quest' => $quest['questions'],
            'answers' => $quest['answers'], 'nm_qt' => $quest['nm_quest'], 'img' => $quest['img']
        ));
    }

    public function retire_triv_indv(Request $request)
    {
        $user = auth()->user()->id;
        $triv = DB::table('triv_indv_user')->where('id_user', $user)
            ->where('id_triv', $request->id_triv)->where('nm_quest', $request->nm_quest)->first();
        if ($request->val_rt == 0) {
            $this->insert_next_question($request->id_triv);
        } else if ($request->val_rt == 1) {
            $wallet = DB::table('Wallets')->where('usuario_id', $user)->first();
            $cdin = DB::table('triv_indv_cdin')->where('id_user', $user)->orderBy('id', 'desc')->first();
            $prize = $triv->multi * $cdin->bet;
            $wallet_admin = Wallet::where('usuario_id', 1)->first();
            Wallet::where("usuario_id", 1)->update(['saldo' => $wallet_admin->saldo - ($prize - $cdin->bet)]);
            DB::table('Wallets')->where('usuario_id', $user)
                ->update(['saldo' => $wallet->saldo + $prize]);
            DB::table('triv_indv_cdin')->where('id_user', $user)->orderBy('id', 'desc')
                ->update(['start' => 2, 'c_half' => 2, 'use_half' => 0, 'sure' => 1]);
            DB::table('triv_indv_user')->where('id_triv', $cdin->id)
                ->where('nm_quest', $request->nm_quest)->update(['lose_win' => 3]);
            $this->clear_quest($cdin->id);
            echo json_encode(array('van' => 0));
        }
    }

    public function time_retire_triv_indv()
    {
        DB::table('triv_indv_cdin')->where('id_user', auth()->user()->id)->orderBy('id', 'desc')
            ->update(['mdl_retire' => time()]);
    }

    public function clear_quest($id)
    {
        DB::table('triv_indv_user')->where('id_triv', $id)->where('lose_win', null)->delete();
    }

    // FIN TRIVIA INDIVIDUAL

    // INCIO TRIVIA PVP
    public function refresh_pvp()
    {
    }
    public function room_pvp()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->join('monedas', 'monedas.id', 'Wallets.coin')->first();
        $sounds = DB::table('audio_tri')->get();
        $cdin = DB::table('triv_indv_cdin')->where('id_user', $user)
            ->orderBy('id', 'desc')->first();
        if ($cdin == null || $cdin->start == 2) {
            return redirect()->back();
        }
        $quest = $this->rand_quest($cdin->id);
        if ($cdin->start == 0) {
            $triv = DB::table('triv_indv_user')->where('id_user', $user)
                ->where('id_triv', $cdin->id)->where('lose_win', null)->first();
        } else {
            $triv = DB::table('triv_indv_user')->where('id_user', $user)
                ->where('id_triv', $cdin->id)->where('lose_win', '!=', null)
                ->orderBy('nm_quest', 'desc')->first();
        }
        $spy = DB::table('triv_indv_user')->where('id_user', $user)
            ->where('id_triv', $cdin->id)->first();
        if ($spy->init_spy == null) {
            $cnt_spy = 7;
        } else {
            $cnt_spy = $triv->init_spy;
        }
        if ($spy->mdl_retire == 0) {
            $tm_ret = 6;
        } else {
            $tm_ret = $spy->mdl_retire;
        }
        $sz_qst = DB::table('triv_indv_user')->where('id_user', $user)
            ->where('id_triv', $cdin->id)->orderBy('nm_quest', 'desc')->first();
        $time_gm = $cdin->end_triv - $cdin->init_triv;
        $dsc_tv = DB::table('discount_triv')->where('usuario_id', $user)->first();

        return view('modules/user/trivia/rm_pvp')->with([
            'wallet' => $wallet,
            'CommerceExchangeRate' => $this->ts(),
            'sound_trv' => $sounds,
            'gain' => $triv->multi * $cdin->bet,
            'c_half' => $cdin->c_half,
            'use_half' => $cdin->use_half,
            'id_triv' => $triv->id_triv,
            's_live' => $triv->lose_win,
            'quest' => $quest['questions'],
            'answers' => $quest['answers'],
            'img' => $quest['img'],
            'tim_end' => $triv->end_quest - time(),
            'xyz' => $quest['respt_inc'],
            'start' => $cdin->start,
            'md_retire' => $cdin->mdl_retire,
            'tm_ret' => $tm_ret,
            'cnt_spy' => $cnt_spy,
            'nm_quest' => $triv->nm_quest,
            'sz_qst' => $sz_qst->nm_quest,
            'time_gm' => $time_gm,
            'desct' => $dsc_tv,
        ]);
    }
    // FIN TRVIA PVP

    public function getCupsRoom($room)
    {
        $trivia = TriviaMulti::where('id', $room)->select('players')->first();
        $players = $trivia->players;

        for ($i = 1; $i <= 10; $i++) {
            if ($i == 10) {
                $cupsWin = ceil(($players * 5) / 100);
                $cups = ($cupsWin <= 2) ? 1 : $cupsWin;

                $ghosts = ceil($cups / 2);
                Ghosts::where('game', 2)->where('room', $room)->update(['ghosts' => $ghosts]);
            } else {
                if ($i == 1) {
                    $cups = ceil(($players * 10) / 100);
                } else {
                    $cupsNumQuest = TriviaMultiCups::where('room', $room)->where('num_quest', $i - 1)->first();
                    $cups = ceil(($cupsNumQuest->cups * 10) / 100);
                }
            }
            TriviaMultiCups::insert(['room' => $room, 'num_quest' => $i, 'cups' => $cups]);
        }
    }


    public function posRank($id)
    {
        $user = auth()->user()->id;
        $rank = DB::table('triv_multi_user')->join('users', 'users.id', 'triv_multi_user.user')
            ->where('room', $id)->where('lose_win', 1)->orderByDesc('end_gm')->get();
        $triv_user = DB::table('triv_multi_user')->where('room', $id)
            ->where('user', $user)->where('lose_win', 1)->first();
        $r_pos = '';

        if (count($rank) > 0 && !empty($triv_user)) {
            foreach ($rank as $key => $value) {
                if ($value->user == $user) {
                    $r_pos = $key + 1;
                }
            }
        }

        return $r_pos;
    }

    public function count_players($id)
    {
        $data = DB::table("triv_multi_user")->where('room', $id)
            ->whereIn('lose_win', [0, 1])->count();
        return $data;
    }

    public function count_winners($id)
    {
        $data = DB::table("triv_multi_user")->where('room', $id)->where('lose_win', 1)->count();
        return $data;
    }
    public function all_winners($id)
    {
        DB::table('triv_multi_user')->where('room', $id)
            ->where('lose_win', 0)->update(['lose_win' => 6]);
    }

    public function editTrivaMulti($id)
    {
        $wallet = Wallet::where("usuario_id", auth()->user()->id)->first();
        $walletdas = Coin::All();
        return view('modules/admin/trivia/edit')
            ->with('data', $this->getInfoTrivia($id))
            ->with('question', $this->triv_multi_quest($id))
            ->with([
                'monedas' => $walletdas,
                'wallet' => $wallet,
            ]);
    }

    public function getInfoTrivia($id)
    {
        $triviaMulti = TriviaMulti::with(['Status', 'Coin'])->where('id', $id)->first();
        return $triviaMulti;
    }

    public function updateTriviaMulti(Request $request)
    {
        TriviaMulti::where('id', $request->id)->update(
            [
                'nombre' => $request->nombre,
                'fecha' => $request->fecha,
                'precio' => $request->precio,
                'hora' => $request->hora,
                'status' => $request->status,
                'min_pj' => $request->min_pj,
            ]
        );
        if ($request->file('inpFile') != null) {
            $files = $request->file('imagen_question');
            $nombreimg = $files->getClientOriginalName();
            $ruta = public_path('imgSystem/imgTrivia/' . $nombreimg);

            Image::make($request->file('imagen_question'))
                ->resize(320, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($ruta);

            TriviaMulti::where('id', $request->id)->update(['imagen' => $nombreimg]);
        }
        Session::flash('mensaje', 'Proceso Efectuado Con Éxito');
        return redirect()->back();
    }

    public function ts()
    {
        $CommerceExchangeRate = CommerceExchangeRate::first();
        return $CommerceExchangeRate->CommerceExchangeRate_RKE;
    }

    public function triv_multi_quest($id)
    {
        $data = TriviaMultiQuest::where('room', $id)->get();
        return $data;
    }

    public function cups($id)
    {
        $data = DB::table('triv_multi_cups')->where('room', $id)->get();
        return $data;
    }

    public function insertp(Request $request)
    {
        $contador = TriviaMultiQuest::where('room', $request->id_trivia)->count();
        if ($contador == 0) {
            $contador = 1;
        } else {
            $contador += 1;
        }
        $nombreimg = '';
        if ($request->file('imagen_question') != null) {
            $files = $request->file('imagen_question');
            $nombreimg = $files->getClientOriginalName();
            $ruta = public_path('imgSystem/imgquestions/' . $nombreimg);

            Image::make($request->file('imagen_question'))
                ->resize(320, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($ruta);
        }
        TriviaMultiQuest::create([
            'num_quest' => $contador,
            'question' => $request->question,
            'correct_answer' => $request->correcta,
            'option_1' => $request->option_1,
            'option_2' => $request->option_2,
            'option_3' => $request->option_3,
            'imagen' => $nombreimg,
            'room' => $request->id_trivia
        ]);

        Session::flash('mensaje', 'Proceso efectuado con exito');
        return redirect()->back();
    }

    public function update_p(Request $request)
    {
        if ($request->imgQ_e == null) {
        } else {
            $files = $request->file('imgQ_e');
            $nombreimg = $files->getClientOriginalName();
            $files->move('imgSystem/imgquestions/', $nombreimg);
        }
        TriviaMultiQuest::where('id', $request->id_p)->update(
            [
                'question' => $request->question_e,
                'correct_answer' => $request->correcta,
                'option_1' => $request->option_1_e,
                'option_2' => $request->option_2_e,
                'option_3' => $request->option_3_e,
                'imagen' => $nombreimg
            ]
        );

        Session::flash('mensaje', 'Proceso Efectuado Con Exito');
        return redirect()->back();
    }

    public function infoGame($id)
    {
        $array = [];
        $rda = rand(0, 3);
        $bandera = $rda;
        $bandera2 = '';
        $bandera3 = '';

        $array_ri = [];
        $rda_p = rand(0, 2);
        $van = $rda_p;
        $van2 = '';

        $triv_user = DB::table('triv_multi_user')->where('room', $id)
            ->where('user', auth()->user()->id)->first();
        $triv_multi_quest = TriviaMultiQuest::where('room', $id)
            ->where('num_quest', $triv_user->num_quest)->get();

        foreach ($triv_multi_quest as $key => $value) {
            $pr = $value->question;
            $idpr = $value->id;
            $img = $value->imagen;
            $array[$rda] = $value->correct_answer;
        }
        for ($i = 0; $i < 5; $i++) {
            $rda = rand(0, 3);
            if ($bandera <> $rda) {
                $bandera2 = $rda;
                break;
            }
            $i = 0;
        }
        foreach ($triv_multi_quest as $key => $value) {
            $array[$rda] = $value->option_1;
        }
        for ($i = 0; $i < 5; $i++) {
            $rda = rand(0, 3);
            if ($bandera <> $rda && $bandera2 <> $rda) {
                $bandera3 = $rda;
                break;
            }
            $i = 0;
        }
        foreach ($triv_multi_quest as $key => $value) {
            $array[$rda] = $value->option_2;
        }
        for ($i = 0; $i < 5; $i++) {
            $rda = rand(0, 3);
            if ($bandera <> $rda && $bandera2 <> $rda && $bandera3 <> $rda) {
                break;
            }
            $i = 0;
        }
        foreach ($triv_multi_quest as $key => $value) {
            $array[$rda] = $value->option_3;
        }

        //50/50
        foreach ($triv_multi_quest as $key => $value) {
            $array_ri[$rda_p] = $value->option_1;
        }
        for ($i = 0; $i < 4; $i++) {
            $rda_p = rand(0, 2);
            if ($van <> $rda_p) {
                $van2 = $rda_p;
                break;
            }
            $i = 0;
        }
        foreach ($triv_multi_quest as $key => $value) {
            $array_ri[$rda_p] = $value->option_2;
        }
        for ($i = 0; $i < 4; $i++) {
            $rda_p = rand(0, 2);
            if ($van <> $rda_p && $van2 <> $rda_p) {
                break;
            }
            $i = 0;
        }
        foreach ($triv_multi_quest as $key => $value) {
            $array_ri[$rda_p] = $value->option_3;
        }
        return array(
            'respt_inc' => $array_ri, 'respuesta' => $array,
            'qtions' => $pr, 'numpp' => $triv_user->num_quest, "idpr" => $idpr, 'img' => $img
        );
    }

    public function send_prizes($id)
    {
        $trivia = TriviaMulti::where('id', $id)->first();
        $all_wins = DB::table('wn_ghosts')->where('game', 2)->where('id_game', $id)->first();
        $awards = $trivia->jackpot / $all_wins->winners;
        $com_ghost = $awards * $all_wins->ghosts;
        $array = DB::table('triv_multi_user')->where('room', $id)
            ->where('lose_win', 1)->pluck('user');
        if ($trivia->status == 3) {
            if (count($array) > 0) {
                foreach ($array as $key => $value) {
                    $hist = DB::table('hist_games')->where('user', $value)
                        ->where('game', 2)->where('id_game', $id)->first();
                    if ($hist->gain == null) {
                        $wallet = Wallet::where('usuario_id', $value)->first();
                        if ($trivia->coin == 1) {
                            Wallet::where("usuario_id", $value)
                                ->update(['usdt' => $wallet->usdt + $awards]);
                        } else {
                            Wallet::where("usuario_id", $value)
                                ->update(['saldo' => $wallet->saldo + $awards]);
                        }
                        DB::table('hist_games')->where('user', $value)->where('game', 2)
                            ->where('coin', $trivia->coin)->where('id_game', $id)
                            ->update(['gain' => $awards]);
                    }
                }
            } else {
                $com_ghost = $trivia->jackpot;
            }
            $walletADM = Wallet::where('usuario_id', 1)->where('user_type', 2)->first();
            if ($trivia->coin == 1) {
                Wallet::where("usuario_id", 1)->where('user_type', 2)
                    ->update(['usdt' => $walletADM->usdt + $com_ghost]);
            } else {
                Wallet::where("usuario_id", 1)->where('user_type', 2)
                    ->update(['saldo' => $walletADM->saldo + $com_ghost]);
            }

            $this->bonus_rand($id); // Repartir bonus aleatorio

            TriviaMulti::where('id', $id)->update(['status' => 4]);
        }
        $this->end_gm($id);
    }

    public function bonus_rand($id) //Ramdon Bonus Para los que superen la Quest 5
    {
        $players = DB::table('triv_multi_user')->where('room', $id)
            ->where('num_quest', '>', 5)->where('lose_win', '!=', 1)->pluck('user')->toArray();
        $trivia = TriviaMulti::where('id', $id)->first();
        $prize =  number_format(($trivia->precio * $this->ts()->CommerceExchangeRate_RKE) * 40 / 100, 2);
        if ($players <> []) {
            $pj_bonus = [];
            $j = 0;
            while ($j <= 1) {
                $rnd = $players[array_rand($players)];
                if (in_array($rnd, $pj_bonus)) {
                    continue;
                } else {
                    array_push($pj_bonus, $rnd);
                    $j++;
                }
            }
            foreach ($pj_bonus as $key => $value) {
                $wallet = Wallet::where('usuario_id', $value)->first();
                Wallet::where("usuario_id", $value)->update(['saldo' => $wallet->saldo + $prize]);
            }
        }
    }

    public function discount_off()
    {
        date_default_timezone_set('America/Caracas');
        $data = date("h:i:s A");
        $time = strtotime('+30 minute', strtotime($data));
        $dsc_tv = DB::table('discount_triv')->first();
        $array = DB::table('discount_triv')->pluck('usuario_id');
        if ($dsc_tv == null) {
            DB::table('discount_triv')->insertGetId(['usuario_id' => auth()->user()->id, 'time' => $time]);
        } else {
            foreach ($array as $key => $value) {
                if ($value == auth()->user()->id) {
                    break;
                } else {
                    DB::table('discount_triv')->insertGetId(['usuario_id' => auth()->user()->id, 'time' => $time]);
                }
            }
        }
    }

    public function hist_ls($id)
    {
        HistGame::where('game', 2)->where('room', $id)
            ->where('user', auth()->user()->id)->update(['gain' => 0]);
        $this->end_gm($id);
    }

    public function end_gm($id)
    {
        date_default_timezone_set('America/Caracas');
        DB::table('triv_multi_user')->where('room', $id)
            ->where('user', auth()->user()->id)->update(['end_gm' => time()]);
    }

    public function consult_info_winners($room)
    {
        $info_user_winns = ['name' => [], 'foto' => [], 'time' => []];
        $info_nm = DB::table("triv_multi_user")
            ->join('users', 'users.id', 'triv_multi_user.user')
            ->where('room', $room)->where('lose_win', 1)
            ->orderByDesc('end_gm')->get();

        foreach ($info_nm as $key => $value) {
            array_push($info_user_winns['name'], $value->username);
            array_push($info_user_winns['foto'], $value->foto);
            array_push($info_user_winns['time'], $value->end_gm - $value->init_gm);
        }
        //
        //
        return $info_user_winns;
    }

    public function update_info($room)
    {
        date_default_timezone_set('America/Caracas');
        $trivia = TriviaMulti::where('id', $room)->first();
        if ($trivia->signs == 0) {
            $this->QuestSendTelegram($room);
        }

        if (strtotime($trivia->fecha . $trivia->hora) + 375 <= time() && $trivia->status == 3) {
            TriviaMulti::where('id', $room)->update(['status' => 6]);
        }

        $players_live = DB::table('triv_multi_user')->where('lose_win', 0)
            ->where('room', $room)->count();

        if ($players_live == 0) {
            TriviaMulti::where('id', $room)->update(['status' => 4]);
        }

        $info_user_winns = $this->consult_info_winners($room);

        $triv_user = DB::table('triv_multi_user')->where('user', auth()->user()->id)
            ->where('room', $room)->first();
        $count_cups = DB::table('triv_multi_cups')->where('room', $room)
            ->where('num_quest', $triv_user->num_quest)->first();

        $trivia = TriviaMulti::where('id', $room)->first();
        echo json_encode(
            array(
                'pj' => $this->count_players($room), 'wns' => $this->count_winners($room),
                'winns' => $info_user_winns, 'cups' => $count_cups->cups, 'snd' => $trivia->status
            )
        );
    }

    public function respuestas(Request $request)
    {
        $user = auth()->user()->id;
        $room = $request->id;
        $trivia = TriviaMulti::where('id', $room)->first();
        $triv_user = DB::table('triv_multi_user')
            ->where('room', $room)->where('user', $user)->first();
        $triv_cups = DB::table('triv_multi_cups')->where('room', $room)
            ->where('num_quest', $triv_user->num_quest)->first();
        $cups10 = DB::table('triv_multi_cups')
            ->where('room', $room)->where('num_quest', 10)->first();
        $answer = TriviaMultiQuest::where('num_quest', $triv_user->num_quest)
            ->where('room', $room)->first();

        if ($triv_user->c_half_use > 0) {
            DB::table('triv_multi_user')->where('room', $room)
                ->where('user', $user)->update(['c_half_use' => 0]);
        }
        if ($trivia->status == 3) {
            if ($request->resp == '') {
                $this->QuestSendTelegram($room);
                if ($request->vc_jump == 0) {
                    $this->buy_lives($room);
                } else { // 'van'=> 6 Es igual a Comodin JUMP
                    if ($triv_user->num_quest < 6 && $triv_user->c_jump > 0) {
                        DB::table('triv_multi_user')->where('room', $room)
                            ->where('user', $user)->update([
                                'num_quest' => $answer->num_quest + 1, 'c_jump' => $triv_user->c_jump - 1
                            ]);
                        $datos = $this->Question($room);
                        echo json_encode(array(
                            'xyz_w' => $datos['respt_inc'], 'pr' => $datos['qtions'],
                            'question' => $datos['respuesta'], 'van' => 6,
                            'numpp' => $datos['numpp'], 'img' => $datos['img']
                        ));
                        $this->fin_pre($room);
                    } else {
                        echo json_encode(array('van' => 6));
                    }
                }
            } else {
                if ($triv_cups->cups > 0) {
                    if ($answer->correct_answer == $request->resp) {
                        if ($triv_user->num_quest == 10) {
                            DB::table('triv_multi_user')->where('room', $room)
                                ->where('user', $user)->update(['lose_win' => 1]);
                            $this->end_gm($room);
                            DB::table('triv_multi_cups')->where('room', $room)
                                ->where('num_quest', 10)->update(['cups' => $cups10->cups - 1]);
                            if ($cups10->cups == 1) {
                                $this->all_winners($room);
                                $this->send_prizes($room);
                            }
                            echo json_encode(array(
                                'van' => 1,
                                'tm_end_win' => $triv_user->end_gm - $triv_user->init_gm,
                                'num_rank' => $this->posRank($room)
                            ));
                        } else {
                            DB::table('triv_multi_user')->where('room', $room)
                                ->where('user', $user)->update(['num_quest' => $answer->num_quest + 1]);
                            DB::table('triv_multi_cups')->where('room', $room)
                                ->where('num_quest', $triv_user->num_quest)->update(['cups' => $triv_cups->cups - 1]);
                            $datos = $this->Question($room);
                            echo json_encode(array(
                                'xyz_w' => $datos['respt_inc'], 'pr' => $datos['qtions'],
                                'question' => $datos['respuesta'], 'van' => 0,
                                'numpp' => $datos['numpp'], 'img' => $datos['img']
                            ));
                            $this->fin_pre($room);
                        }
                    } else {
                        ///Respuesta Incorrecta 'lose_win=4'
                        DB::table('triv_multi_user')->where('room', $room)
                            ->where('user', $user)->update(['lose_win' => 4]);
                        $this->hist_ls($room);
                        echo json_encode(array('van' => 2));
                    }
                } else {
                    //No Hay cups
                    DB::table('triv_multi_user')->where('room', $room)
                        ->where('user', $user)->update(['lose_win' => 5]);
                    if ($triv_cups->num_quest <= 9) {
                        $this->hist_ls($room);
                        echo json_encode(array('van' => 3));
                    } else {
                        $this->hist_ls($room);
                        echo json_encode(array('van' => 4));
                    }
                }
            }
        } else {
            $this->hist_ls($room);
            echo json_encode(array('van' => 4));
        }
        $this->last_pj($room);
    }

    public function comond_rest(Request $request)
    {
        $user = auth()->user()->id;
        $triv_user = DB::table('triv_multi_user')
            ->where('room', $request->id)->where('user', $user)->first();
        if ($triv_user->lose_win == 0 && $triv_user->num_quest < 6) {
            if ($triv_user->c_half > 0) {
                DB::table('triv_multi_user')->where('room', $request->id)
                    ->where('user', $user)->update([
                        'c_half_use' => $triv_user->num_quest, 'c_half' => $triv_user->c_half - 1
                    ]);
                echo json_encode(array('van' => 1));
            } else {
                echo json_encode(array('van' => 0));
            }
        } else {
            echo json_encode(array('van' => 0));
        }
    }

    public function special_cmdin(Request $request) //Hay que hacer lógica del comodin special
    {
        $ext = DB::table('extras_user')->where('user', auth()->user()->id)->first();
        if ($ext->c_special != null && $ext->c_special > 0) {
            DB::table('triv_multi_user')->where('room', $request->id)
                ->where('user', auth()->user()->id)->update(['lose_win' => 1, 'fin_qt' => null]);
        }
    }

    public function start(Request $request)
    {
        date_default_timezone_set('America/Caracas');
        $user = auth()->user()->id;
        $room = $request->id;
        $datos = $this->Question($room);
        DB::table('triv_multi_user')->where('room', $room)
            ->where('user', $user)->update(['start' => 1, 'init_gm' => time()]);

        $triv_signs = DB::table('triv_multi_signs')
            ->where('room', $room)->where('num_quest', 1)->first();
        if (!empty($triv_signs)) { // Se comprueba si la senal de la primera question fue enviada
            $this->SendTelegram($room, 1);
        }
        $this->fin_pre($room);
        echo json_encode(array(
            'pr' => $datos['qtions'], 'question' => $datos['respuesta'],
            'numpp' => 1, 'img' => $datos['img']
        ));
    }

    public function fin_timeQ(Request $request)
    {
        DB::table('triv_multi_user')->where('room', $request->id)
            ->where('user', auth()->user()->id)->update(['lose_win' => 3]);
        $this->last_pj($request->id);
        $this->hist_ls($request->id);
        echo json_encode(array('van' => 1));
    }

    public function revive($id)
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->join('monedas', 'monedas.id', 'Wallets.coin')->first();
        $trivia = TriviaMulti::where('id', $id)->first();
        $triv_user = DB::table('triv_multi_user')->where('room', $id)->where('user', $user)->first();
        $ext = DB::table('extras_user')->where('user', $user)->first();
        if ($trivia->status == 3) {
            if ($ext->lives > 0) {
                DB::table('triv_multi_user')->where('room', $id)->where('user', $user)
                    ->update(['lose_win' => 0, 'num_quest' => $triv_user->num_quest + 1]);
                DB::table('extras_user')->where('user', $user)->update(['lives' => $ext->lives - 1]);
                $datos = $this->Question($id);
                echo json_encode(array(
                    'rv' => 1, 'mn' => number_format($wallet->saldo, 2),
                    'xyz_w' => $datos['respt_inc'], 'pr' => $datos['qtions'],
                    'question' => $datos['respuesta'], 'van' => 5,
                    'numpp' => $datos['numpp'], 'img' => $datos['img']
                ));
                $this->fin_pre($id);
            } else {
                alert('No tienes vidas');
            }
        } else {
            alert('Se alcanzo la cantidad de ganadores');
        }
    }

    public function buy_lives($id)
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->join('monedas', 'monedas.id', 'Wallets.coin')->first();
        $ext = DB::table('extras')->where('id', 3)->first();
        $ext_u = DB::table('extras_user')->where('user', $user)->first();
        if ($ext_u->lives == null || $ext_u->lives <= 0) {
            if ($wallet->saldo >= $ext->price) {
                DB::table('Wallets')->where('usuario_id', $user)
                    ->update(['saldo' => $wallet->saldo - $ext->price]);
                DB::table('extras_user')->where('user', $user)
                    ->update(['lives' => $ext_u->lives + 1]);
                $this->revive($id);
            } else {
                echo json_encode(array('van' => 6));
            }
        } else {
            $this->revive($id);
        }
    }

    public function last_pj($id)
    {
        $count_live = DB::table("triv_multi_user")
            ->where('room', $id)->where('lose_win', 0)->count();
        $trivia = TriviaMulti::where('id', $id)->first();
        // if ($count_live == 0 && $trivia->status == 3) {
        //     $this->send_prizes($id);
        // }
    }

    public function fin_trivia($id_triv)
    {
        date_default_timezone_set('America/Caracas');
        $triv_multi = TriviaMulti::where('id', $id_triv)->first();
        $dataTime = strtotime('+320 second', strtotime($triv_multi->fecha . $triv_multi->hora));
    }

    public function fin_pre($id)
    {
        date_default_timezone_set('America/Caracas');
        $fin_int = time() + 25;
        DB::table('triv_multi_user')->where('room', $id)
            ->where('user', auth()->user()->id)->update(['fin_qt' => $fin_int]);
    }
}
