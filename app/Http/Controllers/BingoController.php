<?php

namespace App\Http\Controllers;

use App\Events\Games\BingoOFF_GetRoomInfo;
use App\Events\Games\BingoOFF_ProccessDraw;
use App\Traits\UserVal;
use App\Traits\EncryptRKE;
use App\Models\Wallet;
use App\Models\Coin;
use App\Models\BingoRoom;
use App\Models\BingoDraw;
use App\Models\BingoRoomUser;
use App\Models\BingoCtns;
use App\Models\HistGame;
use App\Http\Controllers\Controller;
use App\Models\BingoRoomConfig;
use App\Models\BingoDrawCode;
use App\Models\Commerce;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\GameModes;
use App\Models\JackpotType;
use App\Models\PlayerLink;
use App\Models\Customer;
use App\Models\CustomerExchangeRate;
use App\Models\CustomerUser;
use App\Models\ExchangeRate;
use App\Traits\BingoTrait;
use App\Traits\CustomerTrait;
use App\Traits\Filters;
use App\Traits\GameTrait;
use App\Traits\RouteRKE;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class BingoController extends Controller
{
    use EncryptRKE, UserVal, CustomerTrait, BingoTrait, GameTrait, RouteRKE, Filters;

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

    public function getDateTimeRooms($userType, $userID)
    {
        $rooms = BingoRoom::select('encrypted', 'status', 'date', 'time')
            ->where($userType, decrypt($userID))->whereIn('status', [2, 3])->get();

        return [
            'rooms' => $rooms,
            'timestamp' => Carbon::now('America/Caracas')->timestamp * 1000
        ];
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

    private function getGamesRoomsView($userType, $userID, $data = [])
    {
        // Verificar Cierre de Salas
        $this->checkRoomClosure();

        // Obtener infomacion de Salas de Juego (CARDS)
        switch ($userType) {
            case 'admin': // Admin

                break;
            case 'commerce': // Comercio
                $coinID = $data['coin'];
                $coinSym = Coin::getValue($coinID, 'symbol');
                $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($userID, $coinID);

                $player = Auth::id();
                $moreViewData = ['wallet' => $this->Wallet($this->user()->id)];
                break;
            case 'customer': // Cliente
                $coinID = $data['coin'];
                $coinSym = $coinID;
                $exchangeRate = CustomerExchangeRate::getCustomerCoinValue($userID, $coinID);

                $player = $data['player'];
                $userToken = $data['userToken'];
                break;

            default:
                # code...
                break;
        }

        $rooms = BingoRoom::with(['Config' => function ($query) {
            $query->select('room', 'jackpot_type');
        }, 'PlayersRoom' => function ($query) {
            $query->select('room', 'player_user', 'ctns')
                ->where('player_user', Auth::id());
        }])->select(
            'id',
            $userType,
            'subsidiary',
            'status',
            'date',
            'time',
            'unix_date',
            'coin',
            'ctn_price',
            'players',
            'jackpot_prize',
            'ctns',
            'encrypted'
        )->where($userType, $userID)->whereIn('status', [2, 3])->get();

        // Redirigir si no hay salas disponibles
        if (!count($rooms)) {
            return ['redirect' => route('bingo.all-commerce-rooms', [
                'coin' => encrypt($coinID),
                'user' => encrypt(Auth::id())
            ])];
        }

        $viewData = [
            'title' => 'Bingo Salas de Juego',
            'coinSym' => $coinSym,
            'coin' => encrypt($coinID),
            'exchangeRate' => $exchangeRate ?? 1,
            'rooms' => $rooms,
            'dateTimeRooms' => $this->getDateTimeRooms($userType, encrypt($userID))['rooms'],
            'userType' => $userType,
            'userTypeVal' => encrypt($userID),
            'player' => encrypt($player),
            'userToken' => $userToken ?? null,
            'timestamp' => Carbon::now('America/Caracas')->timestamp * 1000
        ];

        if ($coinID == 1) $viewData['USDT'] = true;

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        // Retorna ruta y datos
        return ['route' => 'modules.game.bingo.game-rooms', 'data' => $viewData];
    }

    public function gamesRoomsAdmin() {}

    public function gamesRoomsCommerce($user, $coin)
    {
        // Vista para todas las salas disponibles o en juego de un comercio 

        // Obtner ID d Comercio a traves del ID de usuario
        $commerce = Commerce::getValueByUserId(decrypt($user), 'id');

        $coin = decrypt($coin);

        $viewData = self::getGamesRoomsView('commerce', $commerce, ['coin' => $coin]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, 'commerce');
    }

    public function gamesRoomsCustomer($customerToken, $userToken, $coin = '')
    {
        // Vista para todas las salas disponibles o en juego de un cliente 
        $customer = $this->validateCustomer($customerToken, $userToken);

        if (isset($customer['Error'])) {
            return response()->json(['message' => $customer['Error']], 404);
        }

        $player = CustomerUser::getById($customer, $userToken, ['id', 'coin']);

        if (!$coin) $coin = $player->coin;

        // Obtener informacion de la vista del cliente
        $viewData = self::getGamesRoomsView('customer', $customer, ['userToken' => $userToken, 'player' => $player->id, 'coin' => $coin]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, 'customer');
    }

    public function generateCarton(Request $request)
    {
        $data = [
            'room' => $request->room ?? null,
            'numOfCtns' => $request->num_ctns ?? 0
        ];

        $room = BingoRoom::getRoomByEncrypted($data['room'], ['id', 'ctn_price']);

        if (!$room) {
            return redirect()->back()->with('message', 'Sala no encontrada')->with('type', 'warning');
        }

        $roomId = $room->id;
        $userType = $request->user_type;

        if ($userType) {

            $userTypeVal = decrypt($request->user_type_val);
            $coin = decrypt($request->coin);
            $player = decrypt($request->player);

            $isLogin = $request->is_login ?? false;
            $data['isLogin'] = $isLogin;
            $data['coin'] = $coin;

            if ($userType == 'customer') {

                $user = CustomerUser::getById($player, ['user_id', 'token']);

                $customerResponse = $this->getUserBalanceFromCustomer($userTypeVal, [$user->user_id, $user->token], $coin);

                if (isset($customerResponse->Error)) {
                    return ['Error' => $customerResponse->Error];
                }

                if ($customerResponse->Data[0]->Amount < $data['numOfCtns'] * $room->ctn_price) {
                    // Validar saldo disponible
                    return ['Error' => 'Saldo insuficiente'];
                }

                $data['customer'] = $userTypeVal;
                $data['playerUser'] = $player ?? null;
            } else {
                $data['commerce'] = $userTypeVal;

                if ($isLogin == true) {
                    $data['playerUser'] = $player;

                    // Determinar la columna de saldo en función de la moneda
                    $columnBalance = $coin == 1 ? 'usdt' : 'balance';
                    $exchangeRate = ExchangeRate::getLastValueByCoin($coin);

                    $saleAmount = ($room->ctn_price * $exchangeRate) * $data['numOfCtns'];
                    $balance = Wallet::getValue($player, $columnBalance);

                    if ($balance < $saleAmount) {
                        return ['Error' => 'Saldo Insuficiente'];
                    }
                } else {
                    $data['PJLink'] =  $player ?? null;

                    if ($request->user_in_game) {
                        $data['country'] = $request->country ?? null;
                        $data['playerPhone'] = $request->phone ?? null;
                        $data['playerName'] = $request->name ?? null;
                        $data['playerLastname'] = $request->last_name ?? null;
                    }
                }
            }
        } else {
            $data['seller'] = true;
            $data['country'] = $request->country ?? null;
            $data['playerPhone'] = $request->phone ?? null;
            $data['playerName'] = $request->name ?? null;
            $data['playerLastname'] = $request->last_name ?? null;
        }

        $cartonStore = $this->handleCartonStore($data);

        if (!isset($cartonStore['Error'])) {

            if (isset($userType)) {

                if ($userType == 'customer') {
                    DB::beginTransaction();
                    try {
                        // Validar transacciones del cliente
                        $this->validateTransaction($userTypeVal);
                        DB::commit();
                    } catch (\Exception $e) {
                        dd($e->getMessage());
                        DB::rollBack();
                        Log::error('Ha ocurrido un error: ' . $e->getMessage());
                    }
                }

                $jackpot = $this->convertPrizeValue($roomId, $coin);
                return response()->json(['jackpot' => $jackpot, 'msg_type' => 'success', 'message' => 'Proceso efectuado con exito']);
            } else {
                Session::flash('message', 'Proceso efectuado con exito');
                Session::flash('PJLink',  $cartonStore);
                return redirect()->back();
            }
        } else {
            if (isset($userType)) {
                return response()->json(['type' => 'warning', 'message' => "Ha ocurrido un error: {$cartonStore['Error']}"]);
            } else {
                return redirect()->back()->with(['type' => 'warning', 'message' => "Ha ocurrido un error: {$cartonStore['Error']}"]);
            }
        }
    }

    private function getGameInfo($userType, $data = [])
    {
        date_default_timezone_set('America/Caracas');

        $columnVal = null;

        switch ($userType) {
            case 'customer':

                $customer = $data['customer'];

                // Obtener informacion de Sala
                $room = BingoRoom::select('id', 'status', 'ctn_price')
                    ->where('customer', $customer)->where('encrypted', $data['room'])
                    ->whereIn('status', [2, 3, 4])->first();

                // Si la sala no existe 
                if (!$room) return false;

                $roomId = $room->id;

                $user = CustomerUser::getByToken($customer, $data['userToken'], ['id', 'coin']);

                $columnName = 'player_user';
                $columnVal = $user->id;
                $coinID = $user->coin;
                $coinSym = $coinID;

                $moreViewData = [
                    'custLogo' => Customer::getValueById($customer, 'logo'),
                    'player' => encrypt($columnVal),
                ];

                if ($room->status == 2) {
                    $isLogin = ['cust', 'off'];
                    $moreViewData['userType'] = 'customer';
                    $moreViewData['userTypeVal'] = encrypt($customer);
                    $moreViewData['ctnPrice'] = $this->convertValueByGame('BINGO', $roomId, $room->ctn_price, 'float');
                    $moreViewData['customerName'] = Customer::getValueById($customer, 'name');
                }

                break;
            case 'all';
            case 'commerce':

                $columnName = 'player_link';

                if (isset($data['PJLink'])) {
                    $roomId = PlayerLink::getValueByLink($data['PJLink'], 'room');

                    $player = PlayerLink::select('id', 'country', 'link')->where('room', $roomId)->first();

                    $columnVal = $player->id;

                    $coin = Coin::getCoinByCountry($player->country, ['id', 'symbol']);

                    $coinID = $coin->id;
                    $coinSym = $coin->symbol;

                    $moreViewData = ['player' => $player->link];
                } else {
                    $roomId = BingoRoom::getRoomValueByEncrypted($data['room'], 'id');

                    if ($data['player']) {
                        $coinID = 1;
                        $coinSym = 'USDT';
                        $saleLink = false;
                    } else {
                        $coinID = 1;
                        $coinSym = 'USDT';
                        $saleLink = true;
                    }

                    $commerceCoinsAvailable = CommerceExchangeRate::getCoinsByCommerce($data['commerce'], 1);
                    $coinsAvailable = Coin::getCoinsById($commerceCoinsAvailable, ['id', 'symbol', 'long_name']);

                    $moreViewData = [
                        'player' => decrypt($data['player']) ?? false,
                        'saleLink' => $saleLink,
                        'coinsAvailable' => $coinsAvailable
                    ];
                }

                $room = BingoRoom::getRoomById($roomId, ['status', 'all_for_one', 'ctn_price']);

                $isLogin = ['comm', 'off'];

                if ($userType == 'commerce' && $room->status == 2) {
                    $moreViewData['userType'] = 'commerce';
                    $moreViewData['userTypeVal'] = encrypt($data['commerce']);
                    $moreViewData['ctnPrice'] = $this->convertValueByGame('BINGO', $roomId, $room->ctn_price, 'float', $coinID);
                }
                break;
            default:
                return false;
                break;
        }

        // Verificar Cierre de Sala
        $this->checkRoomClosure($roomId);

        $room = BingoRoom::with(['Coin'])
            ->select([
                'status',
                'date',
                'time',
                'coin',
                'jackpot',
                'encrypted'
            ])->where('id', $roomId)->first();

        if (!in_array($room->status, [2, 3, 4])) return ['error' => 'Sala no disponible'];

        // Obtener valor formateado del bote
        $jackpot = $this->convertPrizeValue($roomId, $coinID);

        if ($columnVal) {
            // Obtener Cartones del jugador
            $ctns = BingoCtns::getCtnsByPlayerInRoom($roomId, $columnName, $columnVal);
        }

        $infoGame = ($room->status >= 3) ? $this->getResult($room->encrypted, $columnVal) : [];

        $vanDraw = ($room->status > 2) ?
            BingoDraw::select('id', 'chip')->where(['room' => $roomId, 'win' => 1])->first() : [];

        $sort = ($room->status == 3) ?
            BingoDraw::where('room', $roomId)->where('id', '<=', $vanDraw->id)
            ->where('time', '>', time())->get(['code', 'time']) : [];

        $chipsRest = ($room->status == 3) ?
            BingoDraw::where('room', $roomId)->where('time', '>', time())
            ->where('id', '<=', $vanDraw->id)->orderBy('chip')->pluck('chip')->sort()->toArray() : [];


        $codeEncrypt = ($room->status == 3) ? BingoDrawCode::where('room', $roomId)->pluck('sym')->toArray() : [];

        $dataChips = $this->getChips($room->encrypted);

        $viewData = [
            'title' => 'BingoCash',
            'room' => $room,
            'jackpotType' => BingoRoomConfig::getValueByRoom($roomId, 'jackpot_type'),
            'coinSym' => $coinSym,
            'jackpot' => $jackpot,
            'startTime' => $this->convertToUnixTimestamp($room->date, $room->time),
            'chipsRest' => $chipsRest,
            'cartons' => $ctns ?? [],
            'sort' => $sort,
            'encrypt' => $codeEncrypt,
            'key' => env('JSON_ENCRYPTION_KEY'),
            'lastSixChips' => $dataChips['lastSixChips'],
            'lastPostChip' => $dataChips['lastPostChip'],
            'allChips' => $dataChips['allChips'],
            'vanDraw' => $vanDraw ? $vanDraw->chip : '',
            'infoGame' => $infoGame,
            'timeServ' => time()
        ];

        if ($room->status == 2) $viewData['coinID'] = encrypt($coinID);

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.visitor.games.bingo.in-game', 'data' => $viewData];
    }

    public function inGame($player, $room = '')
    {
        // Vista para el juego de la venta conjunta de todos los comercios
        $commerce = BingoRoom::getRoomValueByEncrypted($room, 'commerce');

        $userType = $commerce != 'RKE' ?  'commerce' : 'all';

        $viewData = self::getGameInfo($userType, ['commerce' => $commerce, 'player' => $player, 'room' => $room]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, 'commerce');
    }

    public function inGameCommerce($commerce, $PJLink)
    {
        // Vista para el juego de un comercio especifico
        $commerceID = PlayerLink::getValueByLink($PJLink, 'commerce');

        // Validar el codigo de comercio
        $comerceIDByUUID = Commerce::getValueByCodeUUID($commerce, 'id');

        if ($comerceIDByUUID != $commerceID) return redirect('/');

        $viewData = self::getGameInfo('commerce', ['commerce' => $commerceID, 'PJLink' => $PJLink]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, 'commerce');
    }

    public function inGameCustomer($customerToken, $userToken, $room)
    {
        // Vista para el juego de un cliente especifico

        // Validar Informacion de cliente-user y crear variabales de session
        $response = $this->validateCustomer($customerToken, $userToken);

        if (isset($response['Error'])) {
            return response()->json(['message' => $response['Error']], 404);
        }

        // Obtener informacion de la vista del cliente
        $viewData = self::getGameInfo('customer', [
            'customer' => $response,
            'room' => $room,
            'userToken' => $userToken
        ]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, 'customer');
    }

    public function outGame($dateFilter = 'today')
    {
        // Vista del el juego solo para comprar [TODOS]
        $viewData = self::getGameInfo('all', ['commerce' => 'RKE'], $dateFilter);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, 'commerce');
    }

    public function outGameCommerce($commerce, $room)
    {
        // Vista del el juego para un comercio especifico solo para comprar
        $commerce = Commerce::getValueByCodeUUID($commerce, 'id') ?? decrypt($commerce);

        // Redireccionar al Login si no hay coincidencia    
        if (!$commerce) return redirect()->back();

        $viewData = self::getGameInfo('commerce', ['commerce' => $commerce, 'room' => $room]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, 'commerce');
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

        if (!$room) ['Error' => 'Sala no encontrada'];

        $roomId = $room->id;

        $chipVanID = BingoDraw::where('room', $roomId)->where('win', 1)->value('id');

        if ($chipVanID) {

            $lastSixChips = ($room->status > 2) ?
                BingoDraw::where('room', $roomId)->where('time', '<=', time())
                ->where('id', '<=', $chipVanID)->orderByDesc('id')->take(7)->pluck('chip')->toArray() : [];

            $sizeArr = count($lastSixChips);

            if ($sizeArr > 1) {

                $sort = BingoDraw::where('room', $roomId)->where('time', '<=', time())
                    ->where('id', '<=', $chipVanID)->pluck('chip')->toArray();

                $lastPos = array_search($lastSixChips[$sizeArr - 1], $sort);

                array_pop($lastSixChips);
            }

            $allChips =  BingoDraw::where('room', $roomId)->where('id', '<=', $chipVanID)
                ->where('time', '<=', time())->orderByDesc('id')->pluck('chip')->toArray();
        }

        return [
            'lastSixChips' => $lastSixChips ?? [],
            'lastPostChip' => $lastPos ?? 0,
            'allChips' => $allChips ?? []
        ];
    }

    public function getResult($room, $columnVal)
    {
        // Obtener la información de los ganadores
        $winnersInfo = self::getInfoWinners($room, $columnVal);

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

    public function getDraw($room)
    {
        $room = BingoRoom::where('encrypted', $room)->first();
        $sort = BingoDraw::where('room', $room->id)->first();
        if ($sort && !$sort->status) {
            Artisan::call('bingooff:dispatch', ['room' => $room]);
        }
    }

    // CRUD //     
    public function getGameRoomsInfo($userType, $userID, $coin) //  Lista Salas CRUD de Juego
    {
        $this->checkRoomClosure();

        switch ($userType) {
            case 'admin': // Admin
                break;
            case 'commerce': // Comercio

                $isLogin = true;
                $coinSym = Coin::getValue($coin, 'symbol');
                $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($userID, $coin);

                $coins = CommerceExchangeRate::getCoinsByCommerce($userID);
                $coins[] = 1; // ID Coin (USDT)
                $coins = Coin::getCoinsById($coins);

                $routeName = 'bingo.all-commerce-rooms';
                $GMRoute =  route('bingo.commerce-game-rooms', ['user' => encrypt(Auth::id()), 'coin' => encrypt($coin)]);

                $moreViewData = [
                    'wallet' => $this->Wallet($this->user()->id),
                    'countries' => Country::getAllAvailableCountries()
                ];
                break;
            case 'customer': // Cliente

                $isLogin = false;

                $custCoin = CustomerExchangeRate::getById($coin, ['coin', 'exchange_rate']);
                $coinSym = $custCoin->coin;
                $exchangeRate = $custCoin->exchange_rate;
                $coins = CustomerExchangeRate::getCoinsIDsByCustomer($userID);

                $returnBtn = false;
                $token = Customer::getValueById($userID, 'token');

                $routeName = 'bingo.all-customer-rooms';
                $GMRoute =  route('bingo.customer-game-rooms', ['user' => $token, 'coin' => $coin]);

                $moreViewData = ['customer' => true, 'userToken' => $token];

                break;

            default:
                return false;
                break;
        }

        // $gameJackpotType = GameJackpotType::getAvailableByGameMode(1, 2);
        // $jackpotType = JackpotType::getByIds($gameJackpotType);

        $rooms = BingoRoom::with([
            'Config.JackpotType',
            'GameMode',
            'PlayersRoom' => function ($query) {
                $query->where('winner', '>', 0);
            },
            'GameExchangeRate' => function ($query) use ($coin) {
                $query->where('coin', $coin)->where('game', 1);
            }
        ])->where($userType, $userID)->orderByDesc('date')
            ->orderByDesc('time')->orderBy('status')->get();

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Salas de Bingo',
            'name' => 'bingocash',
            'returnBtn' => $returnBtn ?? true,
            'routeName' => $routeName,
            'routeRKE' => $this->getRouteRKE(),
            'userType' => $userType,
            'userID' => $userID,
            'coins' => $coins,
            'coin' => $coin,
            'coinSym' => $coinSym,
            'exchangeRate' => $exchangeRate ?? 1,
            'rooms' => $rooms,
            'GMRoute' => $GMRoute,
            'activeRooms' => $rooms->whereIn('status', [2, 3])->count(),
            'jackpotType' => JackpotType::all(),
            'gameModes' => GameModes::all()
        ];

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.game.bingo.form.index', 'data' => $viewData];
    }

    public function allCommerceRooms($commerce, $coin)
    {
        $commerceId = Commerce::getValueById(decrypt($commerce), 'id');

        if (!$commerceId) return redirect()->back();

        $viewData = $this->getGameRoomsInfo('commerce', $commerceId, decrypt($coin));

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, 'commerce');
    }

    public function allCustomerRooms($customer, $coin = '')
    {
        $customerId = Customer::getValueByToken($customer, 'id');

        if (!$customerId) return response()->json(['message' => 'Token del cliente no existe'], 404);

        if (!$coin) {
            $coins = CustomerExchangeRate::getCoinsIDsByCustomer($customerId);
            if (empty($coins)) {
                return response()->json(['message' => 'No se encontraron monedas disponibles'], 404);
            }
            $coin = $coins[0];
            return redirect()->route('bingo.all-customer-rooms', ['user' => $customer, 'coin' => encrypt($coin->id)]);
        }

        $viewData = $this->getGameRoomsInfo('customer', $customerId, decrypt($coin));

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, 'customer');
    }

    public function create(Request $request)
    {
        $data = json_decode($request->user);
        $allowedPrefixes = ['admin', 'commerce', 'customer'];

        if (in_array($data[1], $allowedPrefixes)) {

            $boat = ($request->jackpot_type > 1) ? $request->fixed_jackpot_value : 0;
            $country = $request->country ?? NULL;
            $coin = $request->coin ?? 1; // ID Coin == 1 (USDT)

            $createData = [
                'game_mode' => $request->game_mode,
                'status' => 1,
                'date' => $request->date,
                'time' => $request->time,
                'unix_date' => strtotime("{$request->date} {$request->time}"),
                'country' => $country,
                'coin' => $coin,
                'ctn_price' => $request->ctn_price,
                'jackpot' => $boat,
                'encrypted' => $this->generateUniqueCode('bingo_rooms')
            ];

            if ($data[1] == 'commerce') {
                $createData['commerce'] = Commerce::getValueByCodeUUID($data[0], 'id');
                $createData['subsidiary'] = $request->subsidiary ?? null;
            } else {
                $createData['customer'] = Customer::getValueByToken($data[0], 'id');
                $createData['code_uuid'] = $this->getCodeUUID('BingoRoom');
            }

            date_default_timezone_set('America/Caracas');

            DB::beginTransaction();

            try {

                $room = BingoRoom::create($createData);

                BingoRoomConfig::create([
                    'room' => $room->id,
                    'jackpot_type' => $request->jackpot_type,
                    'fixed_jackpot_value' => $request->fixed_jackpot_value,
                    'room_commn' => $request->room_commission,
                    'serials_commn' => $request->serials_commission,
                    'val_serials' => $request->val_serials,
                    'num_serials' => $request->num_serials,
                    //'val_sm_prizes' => $request->val_sm_prizes,
                ]);

                DB::commit();
                return redirect()->back()->with('message', 'Sala creada con éxito')->with('type', 'success');
            } catch (\Exception $e) {
                dd($e->getMessage());
                DB::rollBack();
                return redirect()->back()->with('msg-error', 'Hubo un problema al crear la sala: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('msg-error', 'No tienes permiso para crear una sala');
    }

    public function update(Request $request, $id)
    {
        date_default_timezone_set('America/Caracas');

        $id = decrypt($id);
        $room = BingoRoom::findOrFail($id);

        $newDate = $request->date;
        $newTime = $request->time;
        $unixDate = strtotime("{$newDate} {$newTime}");

        if ($unixDate < time()) {
            return redirect()->back()->with('message', 'Fecha/Hora no disponible')->with('type', 'warning');
        }

        DB::beginTransaction();

        try {
            $boat = ($request->jackpot_type > 1) ? $request->fixed_jackpot_value : 0;
            $country = $request->country ?? NULL;
            $coin = $request->coin ?? 1;

            $updateData = [
                'date' => $newDate,
                'time' => $newTime,
                'unix_date' => $unixDate,
                'country' => $country,
                'coin' => $coin,
                'ctn_price' => $request->ctn_price,
                'jackpot' => $room->jackpot + $boat,
            ];

            // Solo añadir 'game_mode' si el jackpot es 0
            if ($room->jackpot == 0) {
                $updateData['game_mode'] = $request->game_mode;
            }

            // Actualizar el bingo con los datos construidos
            $room->update($updateData);

            BingoRoomConfig::where('room', $id)->update(
                [
                    'jackpot_type' => $request->jackpot_type,
                    'fixed_jackpot_value' => $request->fixed_jackpot_value,
                    'room_commn' => $request->room_commission,
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

    public function changeStatusRoom($id)
    {
        // Manejador de Status de Salas de Juego

        //BingoRoom::where('id', $id)->update(['status' => $status]);

        //return response()->json(['room' => $room]);
    }

    public function getInfo($id)
    {
        $id = decrypt($id);

        $room = BingoRoom::select('game_mode', 'date', 'time', 'ctn_price', 'jackpot', 'players')->where('id', $id)->firstOrFail();

        $roomConfig = BingoRoomConfig::select('jackpot_type', 'fixed_jackpot_value', 'room_commn', 'serials_commn', 'val_serials', 'num_serials')
            ->where('room', $id)->firstOrFail();

        return response()->json(['room' => $room, 'config' => $roomConfig]);
    }

    public function getInfoByEncrypt($id, $mode = '', $coin = '')
    {
        $roomID = BingoRoom::getRoomValueByEncrypted($id, 'id');

        if (!$roomID) return response()->json(['Error' => 'Sala no encontrada']);

        $room = BingoRoom::select('game_mode', 'date', 'time', 'ctn_price', 'coin', 'jackpot_prize')
            ->where('id', $roomID)->firstOrFail();

        $roomConfig = BingoRoomConfig::select('jackpot_type', 'fixed_jackpot_value', 'room_commn', 'serials_commn', 'val_serials', 'num_serials')
            ->where('room', $roomID)->firstOrFail();

        if ($mode) {

            $customer = BingoRoom::getValueById($roomID, 'customer');

            if ($customer) {
                $isLogin = false;
                $coin = $room->coin;
                $exchangeRate = CustomerExchangeRate::getCustomerCoinValue($customer, $coin);
            } else {
                $coin = decrypt($coin);
                $isLogin = ($coin == 1) ? $this->wallet()->usdt : $this->wallet()->balance;
                $coin = Coin::getValue($coin, 'symbol');
                $exchangeRate = ExchangeRate::getValue($coin);
            }

            $room = [
                'dateTime' => [$room->date, $room->time],
                'coin' => $coin,
                'jackpotAmount' => number_format($room->jackpot_prize, 2, '.'),
                'jackpotType' => $roomConfig->jackpot_type == 1 ? 'Bote' : 'Premio',
                'ctnPrice' => $room->ctn_price
            ];

            return response()->json(['room' => $room, 'exchangeRate' => $exchangeRate ?? 1, 'isLogin' => $isLogin]);
        }

        return response()->json(['room' => $room, 'config' => $roomConfig]);
    }

    public function copy($id)
    {
        $existingRoom = BingoRoom::findOrFail(decrypt($id));
        $existingRoomConfig = BingoRoomConfig::where('room', $existingRoom->id)->firstOrFail();

        $boat = ($existingRoom->jackpot_type > 1) ? $existingRoom->fixed_jackpot_value : 0;
        $country = $existingRoom->country ?? NULL;
        $coin = $existingRoom->coin ?? 1; // ID Coin == 1 (USDT)

        $createData = [
            'game_mode' => $existingRoom->game_mode,
            'status' => 1,
            'date' => $existingRoom->date,
            'time' => $existingRoom->time,
            'unix_date' => strtotime("{$existingRoom->date} {$existingRoom->time}"),
            'country' => $country,
            'coin' => $coin,
            'ctn_price' => $existingRoom->ctn_price,
            'jackpot' => $boat,
            'encrypted' => $this->generateUniqueCode('bingo_rooms')
        ];

        if ($existingRoom->commerce) {
            $createData['commerce'] = $existingRoom->commerce;
            $createData['subsidiary'] = $existingRoom->subsidiary ?? null;
        } else {
            $createData['customer'] = $existingRoom->customer;
            $createData['code_uuid'] = $this->getCodeUUID('BingoRoom');
        }

        date_default_timezone_set('America/Caracas');

        DB::beginTransaction();

        try {

            $newRoom = BingoRoom::create($createData);

            BingoRoomConfig::create([
                'room' => $newRoom->id,
                'jackpot_type' => $existingRoomConfig->jackpot_type,
                'fixed_jackpot_value' => $existingRoomConfig->fixed_jackpot_value,
                'room_commn' => $existingRoomConfig->room_commn,
                'serials_commn' => $existingRoomConfig->serials_commission,
                'val_serials' => $existingRoomConfig->val_serials,
                'num_serials' => $existingRoomConfig->num_serials,
                //'val_sm_prizes' => $existingRoomConfig->val_sm_prizes,
            ]);

            DB::commit();

            return redirect()->back()->with('message', 'Sala copiada con exito')->with('type', 'success');
        } catch (\Exception $e) {
            dd($e->getMessage());
            DB::rollBack();
            return redirect()->back()->with('msg-error', 'Hubo un problema al copiar la sala: ' . $e->getMessage());
        }


        return redirect()->back()->with('msg-error', 'No tienes permiso para copiar una sala');
    }

    public function replay($id)
    {
        BingoRoomConfig::where("room", decrypt($id))->update(['replay' => 1]);
        return redirect()->back()->with('message', 'Esta sala se publicara cada 24h')->with('type', 'success');
    }

    public function publish($id)
    {
        date_default_timezone_set('America/Caracas');

        // Desencripta el ID y busca el registro correspondiente.
        $bingoRoom = BingoRoom::findOrFail(decrypt($id));
        $publicationDate = "{$bingoRoom->date} {$bingoRoom->time}";
        $unixDate = strtotime($publicationDate);

        if ($unixDate < time()) {
            return redirect()->back()->with('message', 'Fecha/Hora no disponible')->with('type', 'warning');
        }

        DB::beginTransaction();

        try {
            // Actualiza el estado y la fecha de publicación.
            $bingoRoom->update([
                'status' => 2,
                'unix_date' => strtotime($publicationDate),
                'publication_date' => $publicationDate
            ]);

            DB::commit();

            // Redirige al usuario con un mensaje de éxito.
            return redirect()->back()->with('message', 'La sala fue publicada')->with('type', 'success');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error al publicar la sala: ' . $th->getMessage());
            return redirect()->back()->with('message', 'Ocurrió un error')->with('type', 'warning');
        }
    }

    public function delete($id)
    {
        // Desencriptar el ID
        $id = decrypt($id);

        if (BingoRoom::getValueById($id, 'jackpot_prize') > 0) {
            return redirect()->back()->with('msg-error', 'No es posible eliminar esta sala');
        }

        DB::beginTransaction();

        try {
            BingoRoomConfig::where('room', $id)->delete();

            // Actualizar el registro en bingo_rooms (o eliminar si decides hacerlo en lugar de solo actualizar el estado)
            BingoRoom::where('id', $id)->delete();

            DB::commit();
            // Redirigir de vuelta con un mensaje
            return redirect()->back()->with('message', 'La sala ha sido eliminada')->with('type', 'success');
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return redirect()->back()->with('msg-error', 'Ha ocurrido un error');
        }
    }

    public function refund($id) //Reembolsar compras de la sala
    {
        $room = decrypt($id);

        $room = BingoRoom::where('id', $room)->first();
        $bg_user = BingoRoomUser::where('room', $room)->get();

        foreach ($bg_user as $key => $value) {

            $user_bg = BingoRoomUser::where('room', $room)->where('user', $value->user)->first();

            $return_balance = ($user_bg->ctns * $room->ctn_price);

            $walleuser_type = Wallet::where('usuario_id', $value->user)->first();

            if ($room->coin == 1) {
                Wallet::where("usuario_id", $value->user)
                    ->update(['usdt' => $walleuser_type->usdt + $return_balance]);
            } else {
                Wallet::where("usuario_id", $value->user)
                    ->update(['saldo' => $walleuser_type->balance + $return_balance]);
            }

            //Comprobar el historial de jugadas
            HistGame::where('game', 1)->where('room', $room)
                ->where('user', $value->id)->update(['gain' => 'Reembolsado']);
        }

        BingoRoom::where('id', $room)->update(['status' => 5]);

        return redirect()->back()->with('message', 'El bote fue reembolsado')->with('type', 'success');;
    }

    public function actWn(Request $request)
    {
        $PJLink = $request->PJLink;

        if (PlayerLink::where('link', $PJLink)->exists()) {
            $room = PlayerLink::where('link', $PJLink)->value('room');
            $this->newDraw($room, $PJLink);
            return redirect()->back()->with('message', 'Carton Ganador Generado');
        } else {
            return redirect()->back()->with('message', 'El PlayerLink no existe');
        }
    }
}
