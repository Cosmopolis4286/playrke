<?php

namespace App\Http\Controllers;

use App\Events\Games\BingoOFF_GetRoomInfo;
use App\Events\Games\BingoOFF_ProccessDraw;
use App\Traits\UserVal;
use App\Traits\EncryptRKE;
use App\Models\Wallet;
use App\Models\Coin;
use App\Models\BingoOffRoom;
use App\Models\BingoOffSort;
use App\Models\BingoOffRoomUser;
use App\Models\BingoOffCtns;
use App\Models\HistGame;
use App\Http\Controllers\Controller;
use App\Models\BingoOffCtnsLot;
use App\Models\BingoOffPrizeCtns;
use App\Models\BingoOffRoomConfig;
use App\Models\BingoOffSortCode;
use App\Models\Commerce;
use App\Models\Country;
use App\Models\ExchangeRate;
use App\Models\JackpotType;
use App\Models\PlayerLink;
use App\Traits\BingoTrait;
use App\Traits\Filters;
use App\Traits\GameTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BingoOfflineController extends Controller
{
    use EncryptRKE, UserVal, BingoTrait, GameTrait, Filters;

    protected $idCommerceRKE;

    public function __construct()
    {
        $this->idCommerceRKE = 1;
    }

    public function prueba($room)
    {
        // event(new BingoOFF_ProccessDraw(now()->format('H:i:s'), $room->encrypted));
        // broadcast(new BingoOFF_GetRoomInfo(now()->format('H:i:s'), $room->encrypted));
    }

    private function getRoomOffGame($query, $dateFilter)
    {
        $dateFilters = [$dateFilter, 'yesterday', 'tomorrow'];
        $room = null;

        foreach ($dateFilters as $filter) {
            $room = $this->applyDateFilter($filter, $query, 'publication_date')->first();
            if ($room) break;
        }

        if (!$room) $room = $query->orderByDesc('id')->first();

        return $room;
    }

    public function offGame($dateFilter = 'today')
    {
        date_default_timezone_set('America/Caracas');

        $queryRoom = BingoOffRoom::where('all_for_one', 1);
        $room = $this->getRoomOffGame($queryRoom, $dateFilter);

        if (!$room) return redirect('/');

        $this->checkRoomClosure(); // Verificar Cierre de Sala

        $roomId = $room->id;

        $room = BingoOffRoom::with(['Coin'])
            ->select(['status', 'date', 'time', 'coin', 'jackpot', 'encrypted'])->where('id', $roomId)->first();

        $title = 'BingoCash';

        $coin = Coin::getById(1, ['id', 'symbol']);

        $this->convertPrizeValue($roomId, $coin->id);

        $vanSort = ($room->status > 2) ?
            BingoOffSort::where(['room' => $roomId, 'win' => 1])->select('id', 'chip')->first() : [];

        $sort = ($room->status == 3) ?
            BingoOffSort::where('room', $roomId)->where('id', '<=', $vanSort->id)
            ->where('time', '>', time())->get(['code', 'time']) : [];

        $chipsRest = ($room->status == 3) ?
            BingoOffSort::where('room', $roomId)->where('time', '>', time())
            ->where('id', '<=', $vanSort->id)->orderBy('chip')->pluck('chip')->sort()->toArray() : [];

        $allChips = ($room->status > 2) ?
            BingoOffSort::where('room', $roomId)->where('id', '<=', $vanSort->id)
            ->where('time', '<', time())->orderByDesc('id')->pluck('chip')->toArray() : [];

        $codeEncrypt = ($room->status == 3) ? BingoOffSortCode::where('room', $roomId)->pluck('sym')->toArray() : [];

        $lastSixChips = $this->getChips($room->encrypted);

        $infoGame = ($room->status > 3) ? $this->getResult($room->encrypted) : [];

        return view('modules.visitor.games.bingo.off-game')->with([
            'title' => $title,
            'room' => $room,
            'jackpotType' => BingoOffRoomConfig::getValueByRoom($roomId, 'jackpot_type'),
            'coin' => $coin->symbol,
            'jackpot' => $this->convertPrizeValue($roomId, $coin->id),
            'startTime' => $this->convertToUnixTimestamp($room->date, $room->time),
            'chipsRest' => $chipsRest,
            'sort' => $sort,
            'encrypt' => $codeEncrypt,
            'key' => env('JSON_ENCRYPTION_KEY'),
            'lastSixChips' => $lastSixChips[0],
            'lastPostChip' => $lastSixChips[1],
            'allChips' => $allChips,
            'vanSort' => $vanSort ? $vanSort->chip : '',
            'infoGame' => $infoGame,
            'timeServ' => time(),
            // Artisan::call('bingooff:dispatch', ['room' => $room->encrypted])
        ]);
    }

    public function inGame($PJLink)
    {
        $player = PlayerLink::where('link', $PJLink)->first();
        if (!$player) return redirect('/');

        $PJLink = $player->id;

        $this->checkRoomClosure(); // Verificar Cierre de Sala

        $roomId = $player->room;

        $room = BingoOffRoom::with(['Coin'])
            ->select(['status', 'date', 'time', 'coin', 'jackpot', 'encrypted'])->where('id', $roomId)->first();

        if (!in_array($room->status, [2, 3, 4])) return redirect('/');

        self::prueba($room);

        $title = 'BingoCash';

        date_default_timezone_set('America/Caracas');

        $coin = Coin::getCoinByCountry($player->country, ['id', 'symbol']);

        $this->convertPrizeValue($roomId, $coin->id);

        $vanSort = ($room->status > 2) ?
            BingoOffSort::where(['room' => $roomId, 'win' => 1])->select('id', 'chip')->first() : [];

        $sort = ($room->status == 3) ?
            BingoOffSort::where('room', $roomId)->where('id', '<=', $vanSort->id)
            ->where('time', '>', time())->get(['code', 'time']) : [];

        $chipsRest = ($room->status == 3) ?
            BingoOffSort::where('room', $roomId)->where('time', '>', time())
            ->where('id', '<=', $vanSort->id)->orderBy('chip')->pluck('chip')->sort()->toArray() : [];

        $allChips = ($room->status > 2) ?
            BingoOffSort::where('room', $roomId)->where('id', '<=', $vanSort->id)
            ->where('time', '<', time())->orderByDesc('id')->pluck('chip')->toArray() : [];

        $codeEncrypt = ($room->status == 3) ? BingoOffSortCode::where('room', $roomId)->pluck('sym')->toArray() : [];

        $lastSixChips = $this->getChips($room->encrypted);

        $ctns = BingoOffCtns::getCtnsByPlayerInRoom($roomId, $PJLink);

        $infoGame = ($room->status > 3) ? $this->getResult($room->encrypted, $PJLink) : [];

        //event(new BingoOFF_ProccessDraw(now()->format('H:i:s'), $room->encrypted));

        return view('modules.visitor.games.bingo.in-game')->with([
            'title' => $title,
            'room' => $room,
            'jackpotType' => BingoOffRoomConfig::getValueByRoom($roomId, 'jackpot_type'),
            'coin' => $coin->symbol,
            'jackpot' => $this->convertPrizeValue($roomId, $coin->id),
            'startTime' => $this->convertToUnixTimestamp($room->date, $room->time),
            'PJLink' => $PJLink,
            'chipsRest' => $chipsRest,
            'cartons' => $ctns,
            'sort' => $sort,
            'encrypt' => $codeEncrypt,
            'key' => env('JSON_ENCRYPTION_KEY'),
            'lastSixChips' => $lastSixChips[0],
            'lastPostChip' => $lastSixChips[1],
            'allChips' => $allChips,
            'vanSort' => $vanSort ? $vanSort->chip : '',
            'infoGame' => $infoGame,
            'timeServ' => time(),
            'encryptionKey' => env('JSON_ENCRYPTION_KEY')
            // Artisan::call('bingooff:dispatch', ['room' => $room->encrypted])
        ]);
    }

    private function hasPurchasedCartons($phone)
    {
        PlayerLink::where('phone', $phone)->first();
        return true;
    }

    public function buyCartonsToTheCommerce(Request $request)
    {
        // Commerce::

        $hasCtns = $this->hasPurchasedCartons($request->phone);

        if ($hasCtns) return response()->json(['room' => $room, 'config' => $roomConfig]);

        $this->ctnStoreOffline(array());
    }

    public function getChips($room)
    {
        date_default_timezone_set('America/Caracas');

        $room = BingoOffRoom::getRoomByEncrypted($room, ['id', 'status']);
        $roomId = $room->id;

        if ($roomId) {
            $vanSort = BingoOffSort::where('room', $roomId)->where('win', 1)->first();
            if ($vanSort) {
                $lastSixChips = ($room->status > 2) ?
                    BingoOffSort::where('room', $roomId)->where('time', '<=', time())
                    ->where('id', '<=', $vanSort->id)->orderByDesc('id')->take(7)->pluck('chip')->toArray() : [];

                $sizeArr = count($lastSixChips);

                $lastPos = false;
                if ($sizeArr > 1) {
                    $sort = BingoOffSort::where('room', $roomId)->where('time', '<=', time())
                        ->where('id', '<=', $vanSort->id)->pluck('chip')->toArray();

                    $lastPos = array_search($lastSixChips[$sizeArr - 1], $sort);
                    array_pop($lastSixChips);
                }

                $allChips =  BingoOffSort::where('room', $roomId)->where('id', '<=', $vanSort->id)
                    ->where('time', '<', time())->orderByDesc('id')->pluck('chip')->toArray();

                return [$lastSixChips, $lastPos !== false ? $lastPos : 0, time(), $allChips];
            }
        }
        return ['', '', time()];
    }

    public function getResult($room, $PJLink = '')
    {
        // Obtener la información de los ganadores
        $winnersInfo = self::getInfoWinners($room, $PJLink);

        // Encriptar los datos de los ganadores
        $encryptedData = self::encryptData($winnersInfo, env('JSON_ENCRYPTION_KEY'));

        // Retornar los datos encriptados
        return $encryptedData;
    }

    public function viewCtn($ctnId)
    {
        return $this->getCtnWinOff($ctnId);
    }

    public function endGame($room)
    {
        $room = BingoOffRoom::where('encrypted', $room)->first();
        if ($room->status == 3) {
            BingoOffRoom::where('encrypted', $room)->update(['status' => 4]);
            //EventsBingoOffRoom::dispatch($room);
            echo json_encode('end');
        }
    }

    public function getSort($room)
    {
        $room = BingoOffRoom::where('encrypted', $room)->first();
        $sort = BingoOffSort::where('room', $room->id)->first();
        if ($sort && !$sort->status) {
            Artisan::call('bingooff:dispatch', ['room' => $room]);
        }
    }

    public function rooms($coin)
    {
        $title = 'Salas Bingo Off';
        $name = 'bingo-off';
        $wallet = $this->Wallet($this->user()->id);
        $coin = decrypt($coin);
        $coinSym = Coin::getCoinValue($coin, 'symbol');
        $commerce = Commerce::getValueByUserId(Auth::id(), 'id');

        $coins = BingoOffCtnsLot::where('commerce', 1)->distinct()->pluck('coin')->toArray();
        $coins[] = 1; // ID Coin (USDT)
        $coins = Coin::getCoinsById($coins);
        $countries = Country::getAllAvailableCountries();

        // $gameJackpotType = GameJackpotType::getAvailableByGameMode(1, 2);
        // $jackpotType = JackpotType::getByIds($gameJackpotType);

        $jackpotType = JackpotType::all();

        $exchangeRate = ExchangeRate::getValue($coin) ?: 1;

        $this->checkRoomClosure();

        $rooms = BingoOffRoom::with([
            'Config.JackpotType',
            'Coin',
            'PlayersRoom' => function ($query) {
                $query->where('winner', '>', 0);
            },
            'GameExchangeRate' => function ($query) use ($coin) {
                $query->where('coin', $coin)->where('game', 1);
            }
        ])->orderBy('status')->orderByDesc('date')
            ->orderByDesc('time')->where('commerce', $commerce)->get();

        return view(
            'modules.commerce.games.bingo.room.index',
            compact('title', 'name', 'wallet', 'rooms', 'coin', 'coinSym', 'exchangeRate', 'countries', 'coins', 'jackpotType')
        );
    }

    public function create(Request $request)
    {
        date_default_timezone_set('America/Caracas');

        $user = $this->user();
        if ($user->user_type == 2 || $user->user_type == 3) {

            DB::beginTransaction();

            try {
                $boat = ($request->jackpot_type > 1) ? $request->fixed_jackpot_value : 0;
                $country = $request->country ?? NULL;
                $coin = $request->coin ?? 1; // ID Coin == 1 (USDT)

                $room = BingoOffRoom::create([
                    'provider' => $request->provider,
                    'commerce' => Commerce::getValueByUserId($user->id, 'id'),
                    'status' => 1,
                    'all_for_one' => 1,
                    'date' => $request->date,
                    'time' => $request->time,
                    'unix_date' => strtotime($request->date . ' ' . $request->time),
                    'country' => $country,
                    'coin' => $coin,
                    'ctn_price' => $request->ctn_price,
                    'jackpot' => $boat,
                    'encrypted' => $this->roomEncrypt('bingo_off_rooms')
                ]);

                BingoOffRoomConfig::create([
                    'room' => $room->id,
                    'jackpot_type' => $request->jackpot_type,
                    'fixed_jackpot_value' => $request->fixed_jackpot_value,
                    'commerce_commn' => $request->commerce_commission,
                    'val_serials' => $request->val_serials,
                    'num_serials' => $request->num_serials,
                    //'val_sm_prizes' => $request->val_sm_prizes,
                ]);

                DB::commit();

                return redirect()->back()->with('message', 'Sala creada correctamente')->with('type', 'success');
            } catch (\Exception $e) {
                dd($e->getMessage());
                DB::rollBack();
                return redirect()->back()->with('error', 'Hubo un problema al crear la sala: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('error', 'No tienes permiso para crear una sala');
    }

    public function update(Request $request, $id)
    {
        date_default_timezone_set('America/Caracas');

        $id = decrypt($id);
        $bingo = BingoOffRoom::findOrFail($id);

        DB::beginTransaction();

        try {
            $boat = ($request->jackpot_type > 1) ? $request->fixed_jackpot_value : 0;
            $country = $request->country ?? NULL;
            $coin = $request->coin ?? 1;

            $bingo->update([
                'date' => $request->date,
                'time' => $request->time,
                'unix_date' => strtotime($request->date . ' ' . $request->time),
                'country' => $country,
                'coin' => $coin,
                'ctn_price' => $request->ctn_price,
                'jackpot' => $bingo->jackpot + $boat,
            ]);

            BingoOffRoomConfig::where('room', $id)->update(
                [
                    'jackpot_type' => $request->jackpot_type,
                    'fixed_jackpot_value' => $request->fixed_jackpot_value,
                    'commerce_commn' => $request->commerce_commission,
                    'val_serials' => $request->val_serials,
                    'num_serials' => $request->num_serials
                ]
            );

            DB::commit();

            return redirect()->back()->with('message', 'La información fue actualizada')->with('type', 'success');
        } catch (\Exception $e) {
            dd($e->getMessage());
            DB::rollBack();
            return redirect()->back()->with('error', 'Hubo un problema al actualizar la información: ' . $e->getMessage());
        }
    }

    public function getInfo($room)
    {
        $id = decrypt($room);

        $room = BingoOffRoom::where('id', $id)->select('date', 'time', 'ctn_price', 'jackpot')->firstOrFail();

        $roomConfig = BingoOffRoomConfig::where('room', $id)
            ->select('jackpot_type', 'fixed_jackpot_value', 'commerce_commn', 'val_serials', 'num_serials')->firstOrFail();

        return response()->json(['room' => $room, 'config' => $roomConfig]);
    }

    public function copy($id)
    {
        $user = $this->user();
        if ($user->user_type == 2 || $user->user_type == 3) {

            DB::beginTransaction();

            try {
                $existingRoom = BingoOffRoom::findOrFail(decrypt($id));
                $existingRoomConfig = BingoOffRoomConfig::where('room', $existingRoom->id)->firstOrFail();

                // Crear una nueva sala a partir de la existente
                $newRoom = BingoOffRoom::create([
                    'provider' => $existingRoom->provider,
                    'commerce' => $existingRoom->commerce,
                    'status' => 1,
                    'date' => now()->format('Y-m-d'),
                    'time' => $existingRoom->time,
                    'unix_date' => strtotime(now()->format('Y-m-d') . ' ' . $existingRoom->time),
                    'country' => $existingRoom->country,
                    'coin' => $existingRoom->coin,
                    'ctn_price' => $existingRoom->ctn_price,
                    'encrypted' => $this->roomEncrypt('bingo_off_rooms')
                ]);

                // Crear la configuración de la nueva sala
                BingoOffRoomConfig::create([
                    'room' => $newRoom->id,
                    'jackpot_type' => $existingRoomConfig->jackpot_type,
                    'fixed_jackpot_value' => $existingRoomConfig->fixed_jackpot_value,
                    'commerce_commn' => $existingRoomConfig->commerce_commn,
                    'val_serials' => $existingRoomConfig->val_serials,
                    'num_serials' => $existingRoomConfig->num_serials,
                    //'val_sm_prizes' => $existingRoomConfig->val_sm_prizes,
                ]);

                DB::commit();

                return redirect()->back()->with('message', 'Sala copiada con exito')->with('type', 'success');
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Hubo un problema al copiar la sala: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('error', 'No tienes permiso para copiar una sala');
    }

    public function replay($id)
    {
        BingoOffRoomConfig::where("room", decrypt($id))->update(['replay' => 1]);
        return redirect()->back()->with('message', 'Esta sala se publicara cada 24h')->with('type', 'success');
    }

    public function publish($id)
    {
        // Desencripta el ID y busca el registro correspondiente.
        $bingoOffRoom = BingoOffRoom::findOrFail(decrypt($id));

        // Actualiza el estado y la fecha de publicación.
        $bingoOffRoom->update(['status' => 2, 'publication_date' => Carbon::now('America/Caracas')]);

        // Redirige al usuario con un mensaje de éxito.
        return redirect()->back()->with('message', 'La sala fue publicada')->with('type', 'success');
    }

    public function delete($id)
    {
        BingoOffRoom::destroy(decrypt($id));
        return redirect()->back()->with('message', 'La sala ha sido eliminada')->with('type', 'success');
    }

    public function refund($id) //Reembolsar compras de la sala
    {
        $room = decrypt($id);

        $room = BingoOffRoom::where('id', $room)->first();
        $bg_user = BingoOffRoomUser::where('room', $room)->get();
        foreach ($bg_user as $key => $value) {
            $user_bg = BingoOffRoomUser::where('room', $room)
                ->where('user', $value->user)->first();
            $return_balance = ($user_bg->ctns * $room->ctn_price);
            $walleuser_type = Wallet::where('usuario_id', $value->user)->first();
            if ($room->coin == 1) {
                Wallet::where("usuario_id", $value->user)
                    ->update(['usdt' => $walleuser_type->usdt + $return_balance]);
            } else {
                Wallet::where("usuario_id", $value->user)
                    ->update(['saldo' => $walleuser_type->saldo + $return_balance]);
            }
            //Comprobar el historial de jugadas
            HistGame::where('game', 1)->where('room', $room)
                ->where('user', $value->id)->update(['gain' => 'Reembolsado']);
            //Comprobar el historial de jugadas
        }
        BingoOffRoom::where('id', $room)->update(['status' => 5]);
        return redirect()->back()->with('message', 'El bote fue reembolsado')->with('type', 'success');;
    }

    public function actWn(Request $request)
    {
        $PJLink = $request->PJLink;

        if (PlayerLink::where('link', $PJLink)->exists()) {
            $room = PlayerLink::where('link', $PJLink)->value('room');
            $this->newSortOff($room, $PJLink);
            return redirect()->back()->with('message', 'Carton Ganador Generado');
        } else {
            return redirect()->back()->with('message', 'El PlayerLink no existe');
        }
    }
}
