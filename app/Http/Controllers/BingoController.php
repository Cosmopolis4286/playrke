<?php

namespace App\Http\Controllers;

use App\Events\Games\BingoOFF_GetRoomInfo;
use App\Events\Games\BingoOFF_ProccessDraw;
use App\Traits\UserVal;
use App\Traits\EncryptRKE;
use App\Models\Wallet;
use App\Models\Coin;
use App\Models\BingoRoom;
use App\Models\BingoSort;
use App\Models\BingoRoomUser;
use App\Models\BingoCtns;
use App\Models\HistGame;
use App\Http\Controllers\Controller;
use App\Models\BingoCtnsLot;
use App\Models\BingoRoomConfig;
use App\Models\BingoSortCode;
use App\Models\Commerce;
use App\Models\Country;
use App\Models\ExchangeRate;
use App\Models\GameModes;
use App\Models\JackpotType;
use App\Models\PlayerLink;
use App\Models\ProviderRKE;
use App\Models\User;
use App\Models\UserType;
use App\Traits\BingoTrait;
use App\Traits\Filters;
use App\Traits\GameTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BingoController extends Controller
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

    private function getRoomGame($query, $dateFilter)
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

    public function gamesRooms($user, $gameMode = 1) // Vista Salas de Juego 
    {
        $userTypeID = User::getValueById($user, 'user_type');
        $userType = UserType::getValueById($userTypeID, 'prefix');

        //$user = $this->getUserInfoFromProvider($provider, 'hhhh');
        $userTypeVal = false;

        switch ($userType) {
            case 2: // Admin

                break;
            case 3: // Comercio
                $userTypeVal = Commerce::getValueByUserId($user, 'id');
                break;
            case 5: // Proveedor
                $userTypeVal = ProviderRKE::getValueByUserId($user, 'id');
                break;

            default:
                # code...
                break;
        }

        // pvrResponse = $this->getUserInfoFromProvider($provider, $user);

        $rooms = BingoRoom::where($userType, $userTypeVal)
            ->where('game_mode', $gameMode)->whereIn('status', [2, 3])->get();

        $title = 'Bingo Salas de Juego';

        return view('modules.provider.games.bingo.game-rooms')->with([
            'title' => $title,
            'rooms' => $rooms,
        ]);
    }

    public function inGameProvider($room, $provider)
    {
        //Obtener user_id de la variable de session
        $userID = session('userPvr')[0];

        $player = PlayerLink::where('link', $provider)->first();
        if (!$player) return redirect('/');

        $PJLink = $player->id;

        $this->checkRoomClosure(); // Verificar Cierre de Sala

        $roomId = $player->room;

        $room = BingoRoom::with(['Coin'])
            ->select(['status', 'date', 'time', 'coin', 'jackpot', 'encrypted'])->where('id', $roomId)->first();

        if (!in_array($room->status, [2, 3, 4])) return redirect('/');

        self::prueba($room);

        $title = 'BingoCash';

        date_default_timezone_set('America/Caracas');

        $coin = Coin::getCoinByCountry($player->country, ['id', 'symbol']);

        $this->convertPrizeValue($roomId, $coin->id);

        $vanSort = ($room->status > 2) ?
            BingoSort::where(['room' => $roomId, 'win' => 1])->select('id', 'chip')->first() : [];

        $sort = ($room->status == 3) ?
            BingoSort::where('room', $roomId)->where('id', '<=', $vanSort->id)
            ->where('time', '>', time())->get(['code', 'time']) : [];

        $chipsRest = ($room->status == 3) ?
            BingoSort::where('room', $roomId)->where('time', '>', time())
            ->where('id', '<=', $vanSort->id)->orderBy('chip')->pluck('chip')->sort()->toArray() : [];

        $allChips = ($room->status > 2) ?
            BingoSort::where('room', $roomId)->where('id', '<=', $vanSort->id)
            ->where('time', '<', time())->orderByDesc('id')->pluck('chip')->toArray() : [];

        $codeEncrypt = ($room->status == 3) ? BingoSortCode::where('room', $roomId)->pluck('sym')->toArray() : [];

        $lastSixChips = $this->getChips($room->encrypted);

        $ctns = BingoCtns::getCtnsByPlayerInRoom($roomId, $PJLink);

        $infoGame = ($room->status > 3) ? $this->getResult($room->encrypted, $PJLink) : [];

        //event(new Bingo_ProccessDraw(now()->format('H:i:s'), $room->encrypted));

        return view('modules.provider.games.bingo.in-game')->with([
            'title' => $title,
            'room' => $room,
            'jackpotType' => BingoRoomConfig::getValueByRoom($roomId, 'jackpot_type'),
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
            // Artisan::call('Bingo:dispatch', ['room' => $room->encrypted])
        ]);
    }

    public function outGame($dateFilter = 'today')
    {
        date_default_timezone_set('America/Caracas');

        $queryRoom = BingoRoom::where('all_for_one', 1);
        $room = $this->getRoomGame($queryRoom, $dateFilter);

        if (!$room) return redirect('/');

        $this->checkRoomClosure(); // Verificar Cierre de Sala

        $roomId = $room->id;

        $room = BingoRoom::with(['Coin'])
            ->select(['status', 'date', 'time', 'coin', 'jackpot', 'encrypted'])->where('id', $roomId)->first();

        $title = 'BingoCash';

        $coin = Coin::getById(1, ['id', 'symbol']);

        $this->convertPrizeValue($roomId, $coin->id);

        $vanSort = ($room->status > 2) ?
            BingoSort::where(['room' => $roomId, 'win' => 1])->select('id', 'chip')->first() : [];

        $sort = ($room->status == 3) ?
            BingoSort::where('room', $roomId)->where('id', '<=', $vanSort->id)
            ->where('time', '>', time())->get(['code', 'time']) : [];

        $chipsRest = ($room->status == 3) ?
            BingoSort::where('room', $roomId)->where('time', '>', time())
            ->where('id', '<=', $vanSort->id)->orderBy('chip')->pluck('chip')->sort()->toArray() : [];

        $allChips = ($room->status > 2) ?
            BingoSort::where('room', $roomId)->where('id', '<=', $vanSort->id)
            ->where('time', '<', time())->orderByDesc('id')->pluck('chip')->toArray() : [];

        $codeEncrypt = ($room->status == 3) ? BingoSortCode::where('room', $roomId)->pluck('sym')->toArray() : [];

        $lastSixChips = $this->getChips($room->encrypted);

        $infoGame = ($room->status > 3) ? $this->getResult($room->encrypted) : [];

        return view('modules.visitor.games.bingo.out-game')->with([
            'title' => $title,
            'room' => $room,
            'jackpotType' => BingoRoomConfig::getValueByRoom($roomId, 'jackpot_type'),
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

        $room = BingoRoom::with(['Coin'])
            ->select(['status', 'date', 'time', 'coin', 'jackpot', 'encrypted'])->where('id', $roomId)->first();

        if (!in_array($room->status, [2, 3, 4])) return redirect('/');

        self::prueba($room);

        $title = 'BingoCash';

        date_default_timezone_set('America/Caracas');

        $coin = Coin::getCoinByCountry($player->country, ['id', 'symbol']);

        $this->convertPrizeValue($roomId, $coin->id);

        $vanSort = ($room->status > 2) ?
            BingoSort::where(['room' => $roomId, 'win' => 1])->select('id', 'chip')->first() : [];

        $sort = ($room->status == 3) ?
            BingoSort::where('room', $roomId)->where('id', '<=', $vanSort->id)
            ->where('time', '>', time())->get(['code', 'time']) : [];

        $chipsRest = ($room->status == 3) ?
            BingoSort::where('room', $roomId)->where('time', '>', time())
            ->where('id', '<=', $vanSort->id)->orderBy('chip')->pluck('chip')->sort()->toArray() : [];

        $allChips = ($room->status > 2) ?
            BingoSort::where('room', $roomId)->where('id', '<=', $vanSort->id)
            ->where('time', '<', time())->orderByDesc('id')->pluck('chip')->toArray() : [];

        $codeEncrypt = ($room->status == 3) ? BingoSortCode::where('room', $roomId)->pluck('sym')->toArray() : [];

        $lastSixChips = $this->getChips($room->encrypted);

        $ctns = BingoCtns::getCtnsByPlayerInRoom($roomId, $PJLink);

        $infoGame = ($room->status > 3) ? $this->getResult($room->encrypted, $PJLink) : [];

        //event(new BingoOFF_ProccessDraw(now()->format('H:i:s'), $room->encrypted));

        return view('modules.visitor.games.bingo.in-game')->with([
            'title' => $title,
            'room' => $room,
            'jackpotType' => BingoRoomConfig::getValueByRoom($roomId, 'jackpot_type'),
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

    public function outGameProvider($dateFilter = 'today')
    {
        date_default_timezone_set('America/Caracas');

        $queryRoom = BingoRoom::where('all_for_one', 1);
        $room = $this->getRoomGame($queryRoom, $dateFilter);

        if (!$room) return redirect('/');

        $this->checkRoomClosure(); // Verificar Cierre de Sala

        $roomId = $room->id;

        $room = BingoRoom::with(['Coin'])
            ->select(['status', 'date', 'time', 'coin', 'jackpot', 'encrypted'])->where('id', $roomId)->first();

        $title = 'BingoCash';

        $coin = Coin::getById(1, ['id', 'symbol']);

        $this->convertPrizeValue($roomId, $coin->id);

        $vanSort = ($room->status > 2) ?
            BingoSort::where(['room' => $roomId, 'win' => 1])->select('id', 'chip')->first() : [];

        $sort = ($room->status == 3) ?
            BingoSort::where('room', $roomId)->where('id', '<=', $vanSort->id)
            ->where('time', '>', time())->get(['code', 'time']) : [];

        $chipsRest = ($room->status == 3) ?
            BingoSort::where('room', $roomId)->where('time', '>', time())
            ->where('id', '<=', $vanSort->id)->orderBy('chip')->pluck('chip')->sort()->toArray() : [];

        $allChips = ($room->status > 2) ?
            BingoSort::where('room', $roomId)->where('id', '<=', $vanSort->id)
            ->where('time', '<', time())->orderByDesc('id')->pluck('chip')->toArray() : [];

        $codeEncrypt = ($room->status == 3) ? BingoSortCode::where('room', $roomId)->pluck('sym')->toArray() : [];

        $lastSixChips = $this->getChips($room->encrypted);

        $infoGame = ($room->status > 3) ? $this->getResult($room->encrypted) : [];

        return view('modules.visitor.games.bingo.out-game')->with([
            'title' => $title,
            'room' => $room,
            'jackpotType' => BingoRoomConfig::getValueByRoom($roomId, 'jackpot_type'),
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

    private function hasPurchasedCartons($phone)
    {
        PlayerLink::where('phone', $phone)->first();
        return true;
    }

    public function buyCartonsToTheCommerce(Request $request)
    {
        // Commerce::

        $hasCtns = $this->hasPurchasedCartons($request->phone);

        if ($hasCtns) return response()->json(['room' => [], 'config' => []]);

        $this->cartonStore(array());
    }

    public function getChips($room)
    {
        date_default_timezone_set('America/Caracas');

        $room = BingoRoom::getRoomByEncrypted($room, ['id', 'status']);
        $roomId = $room->id;

        if ($roomId) {
            $vanSort = BingoSort::where('room', $roomId)->where('win', 1)->first();
            if ($vanSort) {
                $lastSixChips = ($room->status > 2) ?
                    BingoSort::where('room', $roomId)->where('time', '<=', time())
                    ->where('id', '<=', $vanSort->id)->orderByDesc('id')->take(7)->pluck('chip')->toArray() : [];

                $sizeArr = count($lastSixChips);

                $lastPos = false;
                if ($sizeArr > 1) {
                    $sort = BingoSort::where('room', $roomId)->where('time', '<=', time())
                        ->where('id', '<=', $vanSort->id)->pluck('chip')->toArray();

                    $lastPos = array_search($lastSixChips[$sizeArr - 1], $sort);
                    array_pop($lastSixChips);
                }

                $allChips =  BingoSort::where('room', $roomId)->where('id', '<=', $vanSort->id)
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
        return $this->getCtnWin($ctnId);
    }

    public function endGame($room)
    {
        $room = BingoRoom::where('encrypted', $room)->first();
        if ($room->status == 3) {
            BingoRoom::where('encrypted', $room)->update(['status' => 4]);
            //EventsBingoRoom::dispatch($room);
            echo json_encode('end');
        }
    }

    public function getSort($room)
    {
        $room = BingoRoom::where('encrypted', $room)->first();
        $sort = BingoSort::where('room', $room->id)->first();
        if ($sort && !$sort->status) {
            Artisan::call('bingooff:dispatch', ['room' => $room]);
        }
    }

    // CRUD //     
    public function allRooms($user, $coin) //  Lista Salas de Juego
    {
        $user = decrypt($user);

        $userTypeID = User::getValueById($user, 'user_type');
        $userType = UserType::getValueById($userTypeID, 'prefix');

        //$user = $this->getUserInfoFromProvider($provider, 'hhhh');
        $userTypeVal = false;

        switch ($userType) {
            case 'admin': // Admin
                break;
            case 'commerce': // Comercio
                $userTypeVal = Commerce::getValueByUserId($user, 'id');
                break;
            case 'provider': // Proveedor
                $userTypeVal = ProviderRKE::getValueByUserId($user, 'id');
                break;

            default:
                # code...
                break;
        }

        $title = 'Salas de Bingo';

        $name = 'bingo';
        $wallet = $this->Wallet($this->user()->id);

        $coins = BingoCtnsLot::where($userType, $userTypeVal)->distinct()->pluck('coin')->toArray();
        $coins[] = 1; // ID Coin (USDT)
        $coins = Coin::getCoinsById($coins);
        $countries = Country::getAllAvailableCountries();

        // $gameJackpotType = GameJackpotType::getAvailableByGameMode(1, 2);
        // $jackpotType = JackpotType::getByIds($gameJackpotType);

        $jackpotType = JackpotType::all();
        $gameModes = GameModes::all();

        $coin = decrypt($coin);
        $coinSym = Coin::getValue($coin, 'symbol');
        $exchangeRate = ExchangeRate::getValue($coin) ?: 1;

        $this->checkRoomClosure();

        $rooms = BingoRoom::with([
            'Config.JackpotType',
            'GameMode',
            'Coin',
            'PlayersRoom' => function ($query) {
                $query->where('winner', '>', 0);
            },
            'GameExchangeRate' => function ($query) use ($coin) {
                $query->where('coin', $coin)->where('game', 1);
            }
        ])->where($userType, $userTypeVal)->orderBy('status')
            ->orderByDesc('date')->orderByDesc('time')->get();

        return view(
            'modules.game.bingo.form.index',
            compact('title', 'name', 'wallet', 'rooms', 'coin', 'coinSym', 'exchangeRate', 'countries', 'coins', 'jackpotType', 'gameModes')
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

                $room = BingoRoom::create([
                    'provider' => $request->provider,
                    'game_mode' => $request->game_mode,
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
                    'encrypted' => $this->roomEncrypt('bingo_rooms')
                ]);

                BingoRoomConfig::create([
                    'room' => $room->id,
                    'jackpot_type' => $request->jackpot_type,
                    'fixed_jackpot_value' => $request->fixed_jackpot_value,
                    'commerce_commn' => $request->commerce_commission,
                    'serials_commn' => $request->serials_commission,
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
        $bingo = BingoRoom::findOrFail($id);

        DB::beginTransaction();

        try {
            $boat = ($request->jackpot_type > 1) ? $request->fixed_jackpot_value : 0;
            $country = $request->country ?? NULL;
            $coin = $request->coin ?? 1;

            $bingo->update([
                'game_mode' => $request->game_mode,
                'date' => $request->date,
                'time' => $request->time,
                'unix_date' => strtotime($request->date . ' ' . $request->time),
                'country' => $country,
                'coin' => $coin,
                'ctn_price' => $request->ctn_price,
                'jackpot' => $bingo->jackpot + $boat,
            ]);

            BingoRoomConfig::where('room', $id)->update(
                [
                    'jackpot_type' => $request->jackpot_type,
                    'fixed_jackpot_value' => $request->fixed_jackpot_value,
                    'commerce_commn' => $request->commerce_commission,
                    'serials_commn' => $request->serials_commission,
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

        $room = BingoRoom::where('id', $id)->select('game_mode', 'date', 'time', 'ctn_price', 'jackpot')->firstOrFail();

        $roomConfig = BingoRoomConfig::where('room', $id)
            ->select('jackpot_type', 'fixed_jackpot_value', 'commerce_commn', 'serials_commn', 'val_serials', 'num_serials')->firstOrFail();

        return response()->json(['room' => $room, 'config' => $roomConfig]);
    }

    public function copy($id)
    {
        $user = $this->user();
        if ($user->user_type == 2 || $user->user_type == 3) {

            DB::beginTransaction();

            try {
                $existingRoom = BingoRoom::findOrFail(decrypt($id));
                $existingRoomConfig = BingoRoomConfig::where('room', $existingRoom->id)->firstOrFail();

                // Crear una nueva sala a partir de la existente
                $newRoom = BingoRoom::create([
                    'game_mode' => $existingRoom->game_mode,
                    'provider' => $existingRoom->provider,
                    'commerce' => $existingRoom->commerce,
                    'status' => 1,
                    'date' => now()->format('Y-m-d'),
                    'time' => $existingRoom->time,
                    'unix_date' => strtotime(now()->format('Y-m-d') . ' ' . $existingRoom->time),
                    'country' => $existingRoom->country,
                    'coin' => $existingRoom->coin,
                    'ctn_price' => $existingRoom->ctn_price,
                    'encrypted' => $this->roomEncrypt('bingo_rooms')
                ]);

                // Crear la configuración de la nueva sala
                BingoRoomConfig::create([
                    'room' => $newRoom->id,
                    'jackpot_type' => $existingRoomConfig->jackpot_type,
                    'fixed_jackpot_value' => $existingRoomConfig->fixed_jackpot_value,
                    'commerce_commn' => $existingRoomConfig->commerce_commn,
                    'serials_commn' => $existingRoomConfig->serials_commn,
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
        BingoRoomConfig::where("room", decrypt($id))->update(['replay' => 1]);
        return redirect()->back()->with('message', 'Esta sala se publicara cada 24h')->with('type', 'success');
    }

    public function publish($id)
    {
        // Desencripta el ID y busca el registro correspondiente.
        $bingoRoom = BingoRoom::findOrFail(decrypt($id));

        // Actualiza el estado y la fecha de publicación.
        $bingoRoom->update(['status' => 2, 'publication_date' => Carbon::now('America/Caracas')]);

        // Redirige al usuario con un mensaje de éxito.
        return redirect()->back()->with('message', 'La sala fue publicada')->with('type', 'success');
    }

    public function delete($id)
    {
        BingoRoom::destroy(decrypt($id));
        return redirect()->back()->with('message', 'La sala ha sido eliminada')->with('type', 'success');
    }

    public function refund($id) //Reembolsar compras de la sala
    {
        $room = decrypt($id);

        $room = BingoRoom::where('id', $room)->first();
        $bg_user = BingoRoomUser::where('room', $room)->get();
        foreach ($bg_user as $key => $value) {
            $user_bg = BingoRoomUser::where('room', $room)
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
        BingoRoom::where('id', $room)->update(['status' => 5]);
        return redirect()->back()->with('message', 'El bote fue reembolsado')->with('type', 'success');;
    }

    public function actWn(Request $request)
    {
        $PJLink = $request->PJLink;

        if (PlayerLink::where('link', $PJLink)->exists()) {
            $room = PlayerLink::where('link', $PJLink)->value('room');
            $this->newSort($room, $PJLink);
            return redirect()->back()->with('message', 'Carton Ganador Generado');
        } else {
            return redirect()->back()->with('message', 'El PlayerLink no existe');
        }
    }
}
