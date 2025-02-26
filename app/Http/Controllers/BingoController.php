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
use App\Models\PlayHistory;
use App\Http\Controllers\Controller;
use App\Models\BingoCreator;
use App\Models\BingoCtnPrice;
use App\Models\BingoCtnPriceAutomatic;
use App\Models\BingoCtnsLot;
use App\Models\BingoRoomConfig;
use App\Models\BingoDrawCode;
use App\Models\BingoRoomAutomatic;
use App\Models\Commerce;
use App\Models\CommerceCountriesCoins;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\GameModes;
use App\Models\JackpotType;
use App\Models\PlayerLink;
use App\Models\Customer;
use App\Models\CustomerExchangeRate;
use App\Models\CustomerGames;
use App\Models\CustomerUser;
use App\Models\GameRules;
use App\Models\Games;
use App\Models\Room;
use App\Models\Seller;
use App\Models\UserPurchaseCodes;
use App\Models\UserType;
use App\Services\Customer\HandleTransactions;
use App\Services\Game\Bingo\AutoGenerateBingoRooms;
use App\Services\Game\Bingo\HandleBingoRooms;
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

    public function getDateTimeRooms($creatorType, $creatorID)
    {
        $rooms = BingoRoom::select('encrypted', 'status', 'unix_date')
            ->where($creatorType, decrypt($creatorID))->whereIn('status', [2, 3])->get();

        return ['rooms' => $rooms, 'timestamp' => Carbon::now()->timestamp * 1000];
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

    private function getGamesRoomsView($creator, $data = [])
    {
        if (!in_array($data['roomType'], ['private', 'public'])) {
            return ['redirect' => 'redirect-back'];
        }

        // Verificar Cierre de Salas
        (new HandleBingoRooms())->checkRoomClosure();

        // Obtener infomacion de Salas de Juego (CARDS)
        switch ($creator['type']) {
            case 'admin': // Admin

                break;
            case 'commerce': // Comercio
                $creatorID = $creator['id'];

                $isLogin = true;
                $coin = $data['coin'];
                $coinSym = Coin::getValue($coin, 'symbol');
                $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($creatorID, $coin);

                $player = Auth::id();

                $playerColumn = 'player_link';
                $roomUser = $player;

                $player = Commerce::getValueByUserId($player, 'id') ? false : encrypt($player);

                $moreViewData = ['player' => $player];
                break;
            case 'customer': // Cliente

                $creatorID = $creator['id'];

                $isLogin = false;
                $coin = $data['coin'];
                $custCoin = CustomerExchangeRate::getById($coin, ['coin', 'exchange_rate']);
                $coinSym = $custCoin->coin;
                $exchangeRate = $custCoin->exchange_rate;

                $player = $data['player'] ? encrypt($data['player']) : false;

                $playerColumn = 'player_user';
                $roomUser = $data['player'];

                $moreViewData = ['player' => $player, 'userToken' => $data['userToken']];
                break;

            default:
                # code...
                break;
        }

        // Definir roomType
        $roomType = $data['roomType'] === 'private' ? 1 : 2;

        $rooms = BingoRoom::with([
            'Config' => function ($query) {
                $query->select('room', 'jackpot_type');
            },
            'PlayersRoom' => function ($query) use ($playerColumn, $roomUser) {
                $query->select('id', $playerColumn, 'room', 'ctns')
                    ->where($playerColumn, $roomUser);
            }
        ])->select([
            'id',
            $creator['type'],
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
        ])->where($creator['type'], $creatorID)->where('room_type', $roomType)
            ->whereIn('status', [2, 3])->orderBy('unix_date')->get();

        // Redirigir si no hay salas disponibles
        if (!count($rooms) && $creator['type'] === 'commerce') {
            return ['redirect' => route(
                'bingo.all-rooms',
                [
                    'roomType' => $roomType == 1 ? 'public-rooms' : 'private-rooms',
                    'creatorType' => 'commerce',
                    'creatorID' => encrypt($creatorID),
                    'coin' => encrypt($coin)
                ]
            )];
        }

        $creator['id'] = encrypt($creator['id']);

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Bingo Salas de Juego',
            'coinSym' => $coinSym,
            'coin' => encrypt($coin),
            'exchangeRate' => $exchangeRate ?? 1,
            'creator' => $creator,
            'rooms' => $rooms,
            'bingoRules' => $this->getBingoRules(),
            'dateTimeRooms' => $this->getDateTimeRooms($creator['type'], $creator['id']),
            'route' => route('bingo.get-datetime-rooms', ['creatorType' => $creator['type'], 'creatorID' => $creator['id']])
        ];

        if ($coin == 1) $viewData['USDT'] = true;

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }
        // Retorna ruta y datos
        return ['route' => 'modules.game.bingo.game-rooms', 'data' => $viewData];
    }

    public function gamesRoomsAdmin() {}

    public function gamesRoomsCommerce($roomType = 'private', $coin = '')
    {
        // Vista para todas las salas disponibles o en juego de un comercio 

        // Obtner ID d Comercio a traves del ID de usuario
        $commerce = Commerce::getValueByUserId(Auth::id(), 'id');

        if (!$coin) {
            return redirect()->route(
                'bingo.all-rooms',
                [
                    'roomType' => $roomType === 'private' ? 'private-rooms' : 'public-rooms',
                    'creatorType' => 'commerce',
                    'creatorID' => $commerce,
                    'coin' => encrypt(1)
                ]
            );
        }

        $creator = ['type' => 'commerce', 'id' => $commerce];

        $viewData = self::getGamesRoomsView(
            $creator,
            ['coin' => decrypt($coin), 'roomType' => $roomType]
        );


        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function gamesRoomsCustomer($customerToken, $userToken, $coin = '')
    {
        // Si el token del cliente es diferente del token del usuario, se valida el cliente
        if ($customerToken !== $userToken) {
            $customer = $this->validateCustomer($customerToken, $userToken);

            // Verifica si hubo error en la validación del cliente
            if (isset($customer['Error'])) {
                return response()->json(['message' => $customer['Error']], 404);
            }

            $customerUser = CustomerUser::getByToken($customer, $userToken, ['id', 'coin']);
            $coin = CustomerExchangeRate::getCustomerCoinValue($customer, $customerUser->coin, 'id');

            // Redirecciona si el valor de moneda no está disponible tras desencriptar
            if (!$coin) {
                return redirect()->route(
                    'bingo.game-rooms-customer',
                    ['customer' => $customer, 'user' => $userToken, 'coin' => encrypt($coin)]
                );
            } else {
                $player = $customerUser->id;
            }
        } else {
            $customer = Customer::getValueByToken($customerToken, 'id');
            $coin = decrypt($coin);
            $player = false;
        }

        $creator = ['type' => 'customer', 'id' => $customer];

        // Obtiene la información de vista del cliente
        $viewData = self::getGamesRoomsView($creator, [
            'userToken' => $userToken,
            'player' => $player,
            'coin' => $coin
        ]);

        // Retorna la vista con la información
        return $this->handleViewData($viewData, $creator['type']);
    }

    private function handleCartonByCreator($creator, $roomCtnPrice, $creatorCtnPrice, $data, $request)
    {
        $playerColumn = 'player_user';
        $numOfCtns = $data['numOfCtns'];
        $exchangeRate = 1;
        $saleAmount = 0;

        $player = $request->player ?? null ? decrypt($request->player) : null;

        if ($creator['type'] === 'customer') {
            $user = CustomerUser::getById($player, ['token', 'coin']);
            $customerResponse = $this->getUserBalanceFromCustomer($creator['id'], $user->token);

            if (isset($customerResponse['Error'])) {
                Log::error('Error al generar Carton del Cliente/Jugador: ' . $customerResponse['Error']);
                return ['Error' => 'Ha ocurrido un error en la solicitud'];
            }

            $exchangeRate = CustomerExchangeRate::getCustomerCoinValue($creator['id'], $user->coin);
            $saleAmount = ($creatorCtnPrice ?? $roomCtnPrice * $exchangeRate) * $numOfCtns;

            if ($customerResponse['Data'][0]['Amount'] < $saleAmount) {
                return ['Error' => 'Saldo insuficiente'];
            }

            $data += [
                'customer' => $creator['id'],
                'playerUser' => $player ?? null,
                'exchangeRate' => $exchangeRate,
            ];
        } else {
            $data['commerce'] = $creator['id'];

            if ($data['isLogin']) {
                $data['playerUser'] = $player;
                $columnBalance = $data['coin'] == 1 ? 'usdt' : 'balance';
                $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($creator['id'], $data['coin']);
                $saleAmount = ($creatorCtnPrice ?? $roomCtnPrice * $exchangeRate) * $numOfCtns;

                if (Wallet::getValue($player, $columnBalance) < $saleAmount) {
                    return ['Error' => 'Saldo insuficiente'];
                }

                $data['exchangeRate'] = $exchangeRate;
            } else {
                if ($request->purchase_code && !UserPurchaseCodes::existsPurchaseCode($request->purchase_code)) {
                    return ['Error' => 'Código de Compra no existe'];
                }

                if ($player) {
                    $data += [
                        'country' => $request->country ?? null,
                        'playerPhone' => $request->phone ?? null,
                        'playerName' => $request->name ?? null,
                        'playerLastname' => $request->last_name ?? null,
                    ];
                } else {
                    $playerColumn = 'player_link';
                    $data['playerLink'] = $player ?? null;
                }
            }
        }

        return ['data' => $data, 'playerColumn' => $playerColumn];
    }

    private function handleCartonBySeller($data, $request)
    {
        $seller = Seller::getByUserId(Auth::id(), ['id', 'commerce']);

        $data['commerce'] = $seller->commerce;
        $data['seller'] = $seller->id;
        $data['country'] = $request->country;
        $data['coin'] = Coin::getValueByCountry($request->country, 'id');
        $data['playerPhone'] = $request->phone ?? null;
        $data['playerName'] = $request->name ?? null;
        $data['playerLastname'] = $request->last_name ?? null;

        $data['exchangeRate'] = CommerceExchangeRate::getCommerceCoinValue($seller->commerce, $data['coin']);

        return $data;
    }

    public function generateCarton(Request $request)
    {
        $room = BingoRoom::with(['Config:room,jackpot_type'])
            ->select('id', 'room_type', 'ctn_price', 'status')
            ->where('encrypted', $request->room)->first();

        if (!$room || ($room && $room->status > 2)) {
            return redirect()->back()->with('message', 'Sala no encontrada')->with('type', 'warning');
        }

        $bingoCreatorID = BingoCreator::getValueByRoom($room->id, 'id');

        $data = [
            'room' => $room->id,
            'jackpotType' => $room->Config->jackpot_type,
            'bingoCreatorID' => $bingoCreatorID,
            'numOfCtns' => $request->num_ctns ?? 1
        ];

        $roomCtnPrice = $room->ctn_price;
        $creatorType = $request->user_type;

        if ($creatorType) {
            $creator = ['type' => $creatorType, 'id' => decrypt($request->user_type_val)];

            // Adicionar datos al conjunto
            $data['isLogin'] = $request->is_login ?? false;
            $data['coin'] = decrypt($request->coin);

            $creatorCtnPrice = BingoCtnPrice::getCtnPriceByCreator($data['coin'], $bingoCreatorID);

            $response = $this->handleCartonByCreator($creator, $roomCtnPrice, $creatorCtnPrice, $data, $request);

            if (isset($response['Error'])) {
                return response()->json(['type' => 'warning', 'message' => $response['Error']]);
            }

            $data = $response['data'];
            $playerColumn = $response['playerColumn'];
        } else {
            $data = $this->handleCartonBySeller($data, $request);
            $creatorCtnPrice = BingoCtnPrice::getCtnPriceByCreator($data['coin'], $bingoCreatorID);
        }

        // Definir precio del carton
        $ctnPrice = $creatorCtnPrice
            ? $this->calculateCtnPrice($creatorCtnPrice, true)
            : $this->calculateCtnPrice($roomCtnPrice, false, $data['exchangeRate']);

        $data['ctnPrice'] = $ctnPrice;

        // Iniciar Transaccion
        DB::beginTransaction();

        try {
            if ($data['commerce'] || $data['seller']) {
                // Usuario Fuera de Sesion
                if (!isset($data['isLogin'])) {
                    $data = $this->handlePlayerLink($data);
                }
            }

            if (!isset($data['initials'])) {
                // Manejar iniciales del cliente
                $serialText = isset($data['customer'])
                    ? Customer::getValueById($data['customer'], 'brand') ?? strtoupper(bin2hex(random_bytes(2)))
                    : 'RKE';
                $data['initials'] = $serialText;
            }

            // Generar cartones
            $cartonStore = $this->cartonStore($data);

            if (isset($cartonStore['Error'])) {
                DB::rollBack();
                $message = $cartonStore['Error'];
                $response = isset($creator)
                    ? response()->json(['type' => 'warning', 'message' => $message])
                    : redirect()->back()->with(['type' => 'warning', 'message' => $message]);

                return $response;
            }

            DB::commit(); // Confirmar transacción

            if (isset($creator)) {
                if ($creator['type'] === 'customer') {
                    // Validar transacciones del cliente
                    app(HandleTransactions::class)->validateTransactions($creator['id']);
                }

                $jackpot = number_format($this->convertPrizeValue($data['room'], $data['coin']), 2, '.');
                $ctns = BingoRoomUser::getValue($cartonStore, 'ctns');

                return response()->json([
                    'jackpot' => $jackpot,
                    'ctns' => $ctns,
                    'type' => 'success',
                    'message' => 'Proceso efectuado con éxito'
                ]);
            }

            $responseCartonStore = $this->getGameLinkByPlayer($data['commerce'], $data['playerLink'], $room->room_type);
            Session::flash('message', 'Proceso efectuado con éxito');
            Session::flash('playerLink', $responseCartonStore);

            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            $message = 'Error al procesar la solicitud';
            $response = isset($creator)
                ? response()->json(['type' => 'warning', 'message' => $message])
                : redirect()->back()->with(['type' => 'warning', 'message' => $message]);

            return $response;
        }
    }

    public function getBingoRules()
    {
        $rules = GameRules::getValueByGame('BINGO');

        return $rules ?: view("includes.info.game.bingo");
    }

    private function getGameInfo($creator, $data = [])
    {
        switch ($creator['type']) {
            case 'customer':

                $creatorID = $creator['id'];

                // Obtener informacion de Sala
                $room = BingoRoom::select('id', 'status', 'ctn_price')
                    ->where('customer', $creatorID)->where('encrypted', $data['room'])
                    ->whereIn('status', [2, 3, 4])->first();

                // Si la sala no existe 
                if (!$room) return ['error' => 'Sala no encontrada'];

                $roomID = $room->id;
                $isLogin = false;

                $customer = Customer::getById($creatorID, ['name', 'logo', 'token']);
                $customerID = $customer->token;
                $userToken = $customerID;

                $moreViewData = ['custLogo' => "$customer->token/$customer->logo"];

                if (isset($data['userToken'])) {
                    $user = CustomerUser::getByToken($creatorID, $data['userToken'], ['id', 'coin']);
                    $userToken = $data['userToken'];

                    $coinID = $user->coin;
                    $coinSym = $coinID;
                    $roomUser = BingoRoomUser::getValueByColumn($room->id, 'player_user', $user->id, 'id');
                } else {
                    $coinID = $data['coin'];
                    $coinSym = CustomerExchangeRate::getValueById($coinID, 'coin');
                }

                $moreViewData['player'] = isset($roomUser) ? encrypt($roomUser) : false;

                if ($room->status == 2) {
                    $moreViewData['customerName'] = $customer->name;
                }

                $closingRoute = route(
                    'bingo.game-rooms-customer',
                    ['customer' => $customerID, 'user' => $userToken, 'coin' => encrypt($coinID)]
                );
                break;
            case 'RKE';
            case 'commerce':

                $creatorID = $creator['id'];

                if (isset($data['playerLink'])) {

                    $player = $data['playerLink'];

                    $playerLink = PlayerLink::getByLink($player, ['id', 'room', 'country', 'link']);

                    if (!$playerLink) return ['error' => 'Sala no encontrada'];

                    $roomID = $playerLink->room;

                    $isLogin = false;

                    $roomUser =  BingoRoomUser::getValueByColumn($roomID, 'player_link', $playerLink->id, 'id');

                    $coin = Coin::getCoinByCountry($playerLink->country, ['id', 'symbol']);
                    $coinID = $coin->id;
                    $coinSym = $coin->symbol;

                    $coinsAvailable = Coin::getById($coinID, ['id', 'symbol', 'long_name']);
                } else {
                    $roomID = BingoRoom::getValueByEncrypted($data['room'], 'id');

                    if (!$roomID) return ['error' => 'Sala no encontrada'];

                    if (isset($data['player'])) {
                        $isLogin = true;
                        $player = decrypt($data['player']);
                        $coinID = Wallet::getValue($player, 'coin') ?? 1;
                        $coinSym = Coin::getValue($coinID, 'symbol');

                        $coinsAvailable = Coin::getById($coinID, ['id', 'symbol', 'long_name']);

                        $roomUser =  BingoRoomUser::getValueByColumn($roomID, 'player_user', $player, 'id');
                    } else {
                        $isLogin = false;
                        $coinID = $data['coin'];
                        $coinSym = Coin::getValue($coinID, 'symbol');

                        $commerceCoinsAvailable = CommerceExchangeRate::getCoinsByCommerce($creatorID, 1);
                        $coinsAvailable = Coin::getCoinsById($commerceCoinsAvailable, ['id', 'symbol', 'long_name']);
                    }
                }

                $room = BingoRoom::getById($roomID, ['status', 'room_type', 'ctn_price']);

                $moreViewData = [
                    'player' => isset($roomUser) ? encrypt($roomUser) : false,
                    'saleLink' => true,
                    'coinsAvailable' => $coinsAvailable ?? []
                ];

                $bingoCreatorID = BingoCreator::getValueByCreatorRoom($creator, $roomID);
                // Definir precio del carton 
                $bingoCtnPrice = BingoCtnPrice::getCtnPriceByCreator($coinID, $bingoCreatorID)
                    ?? CommerceExchangeRate::getCommerceCoinValue($creatorID, $coinID);

                $moreViewData['ctnPrice'] = $this->formatAmount($bingoCtnPrice, 'float');

                $closingRoute = false;
                break;
            default:
                return false;
                break;
        }

        // Verificar Cierre de Sala
        (new HandleBingoRooms())->checkRoomClosure($roomID);

        $room = BingoRoom::select(['status', 'date', 'time', 'encrypted'])->where('id', $roomID)->first();

        if (!in_array($room->status, [2, 3, 4])) return ['error' => 'Sala no disponible'];

        $vanDraw = ($room->status > 2) ?
            BingoDraw::select('id', 'chip')->where(['room' => $roomID, 'win' => 1])->first() : [];

        $sort = ($room->status == 3) ?
            BingoDraw::where('room', $roomID)->where('id', '<=', $vanDraw->id)
            ->where('time', '>', time())->get(['code', 'time']) : [];

        $chipsRest = ($room->status == 3) ?
            BingoDraw::where('room', $roomID)->where('time', '>', time())
            ->where('id', '<=', $vanDraw->id)->orderBy('chip')->pluck('chip')->sort()->toArray() : [];

        $codeEncrypt = ($room->status == 3) ? BingoDrawCode::where('room', $roomID)->pluck('sym')->toArray() : [];

        $dataChips = $this->getChips($room->encrypted);

        $randomKey = base64_encode(random_bytes(32));

        if (isset($roomUser)) {
            // Obtener Cartones del ID jugador-sala
            $ctns = BingoCtns::getCtnsByPlayerInRoom($roomID, 'room_user', $roomUser);
        }

        $infoGame = ($room->status >= 3) ?
            $this->getResult(
                ['room' => $room->encrypted, 'coin' => $coinID],
                $roomUser ?? null,
                $randomKey
            ) : [];

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'BingoCash',
            'creatorType' => $creator['type'],
            'creatorID' => encrypt($creatorID),
            'room' => $room,
            'jackpotType' => BingoRoomConfig::getValueByRoom($roomID, 'jackpot_type'),
            'coinSym' => $coinSym,
            'jackpot' => $this->convertPrizeValue($roomID, $coinID),
            'startTime' => $this->convertToUnixTimestamp($room->date, $room->time),
            'chipsRest' => $chipsRest,
            'cartons' => $ctns ?? [],
            'sort' => $sort,
            'encrypt' => $codeEncrypt,
            'random' => $randomKey,
            'latestChips' => $dataChips['latestChips'],
            'allChips' => $dataChips['allChips'],
            'vanDraw' => $vanDraw ? $vanDraw->chip : '',
            'infoGame' => $infoGame,
            'timeServ' => $dataChips['timeServ'],
            'bingoRules' => $this->getBingoRules(),
            'closingRoute' => $closingRoute
        ];

        if ($room->status == 2) $viewData['coinID'] = encrypt($coinID);

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.game.bingo.in-game', 'data' => $viewData];
    }

    public function inGame($player, $room = '', $coin = '')
    {
        // Vista para el juego de la venta conjunta de todos los comercios
        $player = PlayerLink::getByLink($player, ['room', 'commerce', 'link']);

        if (!$player) {
            abort(404); // Retorna un error 404 Not Found
        }

        $creator = ['type' => 'RKE', 'id' => $player->commerce];

        $viewData = self::getGameInfo($creator, ['playerLink' => $player->link, 'room' => $player->room]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function inGameCommerce($commerce, $playerLink)
    {
        // Vista para el juego de un comercio especifico
        $commerceID = PlayerLink::getValueByLink($playerLink, 'commerce') ?? abort(404);

        // Obetener codigos Unicos del comercio
        $commerce = Commerce::getById($commerceID, ['code_rke', 'code_uuid']);

        // Verificar si posee un codigo RKE 
        $codeRKE = $commerce->code_rke ? true : false;

        if (!$codeRKE && !$commerce->code_uuid) {
            return redirect('/');
        }

        $creator = ['type' => 'commerce', 'id' => $commerceID];

        $viewData = self::getGameInfo($creator, ['playerLink' => $playerLink]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function inGameCustomer($customerID, $userToken, $room)
    {
        // Vista para el juego de un cliente especifico

        // Validar Informacion de cliente-user y crear variabales de session
        $response = $this->validateCustomer($customerID, $userToken, 'in-game');

        if (isset($response['Error'])) {
            return response()->json(['message' => $response['Error']], 404);
        }

        $creator = ['type' => 'customer', 'id' => $response];

        // Obtener informacion de la vista del cliente
        $viewData = self::getGameInfo($creator, ['room' => $room, 'userToken' => $userToken]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function outGame($room = '', $creator = '', $coin = '')
    {
        // Vista del el juego solo para comprar [TODOS]

        $creator = ['type' => 'RKE', 'id' => $creator];

        $viewData = self::getGameInfo($creator, ['room' => $room]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function outGameCommerce($commerce, $room, $coin)
    {
        // Vista del el juego para un comercio especifico solo para comprar
        $commerce = Commerce::getValueByCodeUUID($commerce, 'id') ?? decrypt($commerce);

        // Redireccionar al Login si no hay coincidencia    
        if (!$commerce) return redirect()->back();

        if (!$room) {
            // Redirecciona si no se envio un ID de sala
            return redirect()->route(
                'bingocash.out-game-commerce',
                ['commerce' => $commerce, 'room' => $room, 'coin' => $coin]
            );
        } else {

            $roomCommerce = BingoRoom::getByEncrypted($room, ['id', 'commerce']);

            if (!$roomCommerce || ($roomCommerce && $roomCommerce->commerce != $commerce)) {
                if (!$commerce) return redirect()->back();
            }
        }

        $creator = ['type' => 'commerce', 'id' => $commerce];

        $viewData = self::getGameInfo($creator, ['room' => $room, 'coin' => decrypt($coin)]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function outGameCustomer($customer, $room, $coin)
    {
        // Vista del el juego para un cliente especifico para comprar y/o visualizar
        $customer = Customer::getValueById(decrypt($customer), 'id');

        // Redireccionar a la vista anterior   
        if (!$customer) return redirect()->back();

        $creator = ['type' => 'customer', 'id' => $customer];

        $viewData = self::getGameInfo($creator, ['room' => $room, 'coin' => decrypt($coin)]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
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
        $room = BingoRoom::getByEncrypted($room, ['id', 'status']);

        if (!$room) ['Error' => 'Sala no encontrada'];

        $chipVanID = BingoDraw::where('room', $room->id)->where('win', 1)->value('id');

        if ($chipVanID) {
            // Realizamos una única consulta para obtener todas las fichas en las condiciones especificadas
            $allChips = BingoDraw::where('room', $room->id)->where('id', '<=', $chipVanID)
                ->where('time', '<=', time())->orderByDesc('id')->pluck('chip')->toArray();

            // Si el estado de la sala es mayor a 2, tomamos los primeros seis elementos de $allChips como $latestChips
            $latestChips = ($room->status > 2) ? array_slice($allChips, 0, 6) : [];
        }

        return [
            'latestChips' => $latestChips ?? [],
            'allChips' => $allChips ?? [],
            'timeServ' => time()
        ];
    }

    public function getResult($data, $roomUser, $randomKey)
    {
        // Obtener la información de los ganadores
        $winnersInfo = self::getInfoWinners($data['room'], $roomUser, $data['coin']);

        // Encriptar los datos de los ganadores
        $encryptedData = self::encryptData($winnersInfo, $randomKey);

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

    private function getRouteCtnPriceForCoin()
    {
        return route(
            'bingo.getCtnPriceForCoin',
            ['creation' => ':1', 'creatorType' => ':2', 'creatorID' => ':3', 'room' => ':4']
        );
    }

    private function getInfoRooms($roomType, $coin, $creator)
    {
        $creatorType = UserType::getValueByPrefix($creator['type']);
        $creatorID = decrypt($creator['id']);

        $roomType = $roomType === 'private-rooms' ? 'PV' : 'PB';

        return BingoRoom::with([
            'Config.JackpotType',
            'Config.GameMode:id,description',
            'Creator' => function ($query) use ($creatorType, $creatorID) {
                $query->select('id', 'room', 'creator_type', 'creator_id')
                    ->where('creator_type', $creatorType)
                    ->where('creator_id', $creatorID);
            },
            'PlayersRoom' => function ($query) {
                $query->where('winner', '>', 0);
            },
            'GameExchangeRate' => function ($query) use ($coin) {
                $query->where('coin', $coin)->where('game', 1);
            }
        ])->orderBy('status')->get();
    }

    private function getGameRoomsInfo($creator, $coin, $roomType)
    {
        (new HandleBingoRooms())->checkRoomClosure();

        $view = $roomType === 'private-rooms' ? 'private' : 'public';

        switch ($creator['type']) {
            case 'admin': // Admin
                break;
            case 'commerce': // Comercio
                $creatorID = decrypt($creator['id']);

                $isLogin = true;
                $coinSym = Coin::getValue($coin, 'symbol');
                $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($creatorID, $coin);

                $coins = CommerceExchangeRate::getCoinsByCommerce($creatorID);
                $coins[] = 1; // ID Coin (USDT)
                $coins = Coin::getCoinsById($coins);

                $routeWithCoin = [
                    'route' => 'bingo.all-rooms',
                    'parameters' => [
                        'roomType' => $roomType,
                        'creatorType' => 'commerce',
                        'creatorID' => $creator['id']
                    ]
                ];

                $GMRoute =  route('bingo.game-rooms-commerce', ['roomType' => $view, 'coin' => encrypt($coin)]);

                $moreViewData = [
                    'commerceUUID' => Commerce::getValueById($creatorID, 'code_uuid'),
                    'countries' => Country::getAllAvailableCountries()
                ];

                break;
            case 'customer': // Cliente

                $creatorID = decrypt($creator['id']);
                $isLogin = $returnBtn = false;

                $customerCoin = CustomerExchangeRate::getById($coin, ['coin', 'exchange_rate']);
                $coinSym = $customerCoin->coin;
                $exchangeRate = $customerCoin->exchange_rate;
                $coins = CustomerExchangeRate::getCoinsIDsByCustomer($creatorID);

                $token = Customer::getValueById($creatorID, 'token');

                $routeWithCoin = [
                    'route' => 'bingo.all-rooms',
                    'parameters' => [
                        'roomType' => $roomType,
                        'creatorType' => 'customer',
                        'creatorID' => $creator['id']
                    ]
                ];
                $GMRoute =  route(
                    'bingo.game-rooms-customer',
                    ['customer' => $token, 'user' => $token, 'coin' => encrypt($coin)]
                );

                $gameID = Games::getValueByName('BINGO');

                $customerGameID = CustomerGames::getByGameID($creatorID, $gameID) ?? null;

                $moreViewData = [
                    'customer' => true,
                    'userToken' => $token,
                    'customerGameID' => $customerGameID
                ];

                break;

            default:
                return false;
                break;
        }

        $rooms = $this->getInfoRooms($roomType, $coin, $creator);

        dd($rooms);

        $routeGetCtnPriceForCoin = $this->getRouteCtnPriceForCoin();

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Salas de Bingo',
            'name' => 'bingocash',
            'returnBtn' => $returnBtn ?? true,
            'routeWithCoin' => $routeWithCoin,
            'routeGetCtnPriceForCoin' => $routeGetCtnPriceForCoin,
            'routeRKE' => $this->getRouteRKE(),
            'creator' => $creator,
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

        return ['route' => 'modules.game.bingo.form.' . $view, 'data' => $viewData];
    }

    public function allRooms($roomType, $creatorType, $creatorID = '', $coin = '')
    {
        $roomTypes = ['automatic-rooms', 'private-rooms', 'public-rooms'];

        if (!in_array($roomType, $roomTypes)) {
            return redirect()->back();
        }

        if ($roomType === 'automatic-rooms') {
            return $this->allAutomaticRooms($creatorType, $creatorID);
        } else {
            switch ($creatorType) {
                case 'admin':
                    // Lógica para "admin" si se necesita.
                    break;
                case 'commerce':
                    return $this->allCommerceRooms($roomType, $creatorID, $coin);
                    break;
                case 'customer':
                    return $this->allCustomerRooms($roomType, $creatorID, $coin);
                    break;
                default:
                    return redirect()->back()->with('error', 'Tipo de creador no válido.');
            }
        }
    }

    private function allCommerceRooms($roomType, $creatorID, $coin = '')
    {
        $commerceId = Commerce::getValueByUserId(Auth::id(), 'id');

        if (!$commerceId) {
            return redirect()->back()->with('error', 'El comercio no existe o no está vinculado al usuario.');
        }

        if (!$creatorID || !$coin) {
            return redirect()->route('bingo.all-rooms', [
                'roomType' => $roomType,
                'creatorType' => 'commerce',
                'creatorID' => encrypt($commerceId),
                'coin' => encrypt(1)
            ]);
        }

        $creator = [
            'type' => 'commerce',
            'id' => encrypt($commerceId),
        ];

        $viewData = $this->getGameRoomsInfo($creator, decrypt($coin), $roomType);

        // Retorna la vista con la información
        return $this->handleViewData($viewData, $creator['type']);
    }

    private function allCustomerRooms($roomType, $customer = '', $coin = '')
    {
        $isLogin = Customer::getValueByUserId(Auth::id(), 'id');

        if (!$isLogin) {
            $customerID = Customer::getValueByToken($customer, 'id');

            // Verificar si el cliente existe
            if (!$customerID) {
                return response()->json(['message' => 'Token del cliente no existe'], 404);
            }
        } else {
            $customerID = $isLogin;
        }

        // Si no se proporciona moneda, obtener la primera disponible
        if (!$coin || !$customer) {

            $coins = CustomerExchangeRate::getCoinsIDsByCustomer($customerID);

            // Verificar si el cliente tiene monedas disponibles
            if (!count($coins)) {
                return response()->json(['message' => 'No se encontraron monedas registradas'], 404);
            }

            // Redirigir con la primera moneda disponible encriptada
            return redirect()->route('bingo.all-rooms', [
                'roomType' => $roomType,
                'creatorType' => 'customer',
                'creatorID' => $isLogin ? encrypt($customerID) : $customerID,
                'coin' => encrypt($coins[0]->id)
            ]);
        }

        try {
            // Intentar desencriptar la moneda
            $coin = decrypt($coin);
        } catch (\Exception $e) {
            if (!CustomerExchangeRate::existsCoinByCustomer($customerID, $coin)) {
                return response()->json(['message' => 'Divisa Invalida'], 404);
            }
            $coin = CustomerExchangeRate::getCustomerCoinValue($customerID, $coin, 'id');
            // Si falla, encriptar el ID la moneda nuevamente y redirigir
            return redirect()->route(
                'bingo.all-rooms',
                [
                    'roomType' => $roomType,
                    'creatorType' => 'customer',
                    'creatorID' => $isLogin ? encrypt($customerID) : $customerID,
                ]
            );
        }

        $creator = ['type' => 'customer', 'id' => encrypt($customerID)];

        // Obtener la información de las salas de juego
        $viewData = $this->getGameRoomsInfo($creator, $coin, $roomType);

        // Retornar la vista con la información
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function create(Request $request)
    {
        $creator = json_decode($request->creator);
        $creatorType = $creator->type;
        $creatorID = decrypt($creator->id);
        $allowedPrefixes = ['admin', 'commerce', 'customer'];

        if (in_array($creatorType, $allowedPrefixes)) {

            date_default_timezone_set(config('app.timezone'));

            $boat = ($request->jackpot_type === 'PV') ? $request->fixed_jackpot_value : 0;
            $coin = $request->coin ?? 1; // ID Coin == 1 (USDT)

            $createData = [
                'encrypted' => $this->generateUniqueCode('bingo_rooms'),
                'status' => 1,
                'jackpot' => $boat,
                'code_uuid' => $this->getCodeUUID('BingoRoom')
            ];

            DB::beginTransaction();

            try {

                $room = BingoRoom::create($createData);

                $roomID = $room->id;

                $creatorType = UserType::getValueByPrefix($creatorType, 'id');
                $ctnPriceCoins = $request->ctn_price_coins;

                // Registar el creador de la sala
                $bingoCreatorID = BingoCreator::create([
                    'room' => $roomID,
                    'creator_type' => $creatorType,
                    'creator_id' => $creatorID,
                    'creator_commn' => $request->room_commission,
                    'ctn_price' => $request->ctn_price,
                ])->id;

                // Actualizar ID BingoCreator
                $room->update(['bingo_creator' => $bingoCreatorID]);

                BingoRoomConfig::create([
                    'room' => $roomID,
                    'room_type' => $request->room_type,
                    'creation' => 'M',
                    'game_mode' => $request->game_mode,
                    'date' => $request->date,
                    'time' => $request->time,
                    'unix_date' => strtotime("{$request->date} {$request->time}"),
                    'coin' => $coin,
                    'ctn_price' => $request->ctn_price,
                    'jackpot_type' => $request->jackpot_type,
                    'fixed_jackpot_value' => $request->fixed_jackpot_value,
                    'time_chip' => $request->time_chip,
                    'room_commn' => $request->room_commission,
                    'pct_serials' => $request->pct_serials,
                    'num_serials' => $request->num_serials,
                ]);

                if ($ctnPriceCoins) {

                    $timestamp = now();

                    $recordsToInsert = collect($ctnPriceCoins)
                        ->filter(fn($ctnPrice) => $ctnPrice) // Filtrar solo los elementos con valor
                        ->map(function ($ctnPrice, $coin) use ($roomID, $bingoCreatorID, $timestamp) {
                            return [
                                'room' => $roomID,
                                'bingo_creator_id' => $bingoCreatorID,
                                'coin' => $coin,
                                'ctn_price' => $ctnPrice,
                                'created_at' => $timestamp,
                                'updated_at' => $timestamp,
                            ];
                        })->values()->toArray();

                    BingoCtnPrice::insert($recordsToInsert);
                }

                DB::commit();
                return redirect()->back()->with('message', 'Sala creada con éxito')->with('type', 'success');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al crear la sala: ' . $e->getMessage());
                return redirect()->back()->with('msg-error', 'Hubo un problema al crear la sala: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('msg-error', 'No tienes permiso para crear una sala');
    }

    public function update(Request $request, $id)
    {
        $roomID = decrypt($id);
        $room = BingoRoom::findOrFail($roomID);

        date_default_timezone_set(config('app.timezone'));

        $newDate = $request->date;
        $newTime = $request->time;
        $unixDate = strtotime("{$newDate} {$newTime}");

        if ($unixDate < time()) {
            return redirect()->back()->with('message', 'Fecha/Hora no permitidas')->with('type', 'warning');
        }

        DB::beginTransaction();

        try {
            $boat = ($request->jackpot_type === 'PV') ? $request->fixed_jackpot_value : 0;
            $coin = $request->coin ?? 1;
            $ctnPriceCoins = $request->ctn_price_coins;

            $updateData = [
                'date' => $newDate,
                'time' => $newTime,
                'unix_date' => $unixDate,
                'coin' => $coin,
                'ctn_price' => $request->ctn_price,
                'jackpot' => $room->jackpot + $boat,
                'room_type' => $request->room_type ?? 1
            ];

            // Solo añadir 'game_mode' si el jackpot es 0
            if ($room->jackpot == 0) {
                $updateData['game_mode'] = $request->game_mode;
            }

            // Actualizar el bingo con los datos construidos
            $room->update($updateData);

            $bingoCreator = BingoCreator::where('room', $roomID)->firstOrFail();

            $bingoCreator->update([
                'creator_commn' => $request->room_commission,
                'ctn_price' => $request->ctn_price,
            ]);

            $bingoCreatorID = $bingoCreator->id;

            BingoRoomConfig::where('room', $roomID)->update(
                [
                    'jackpot_type' => $request->jackpot_type,
                    'fixed_jackpot_value' => $request->fixed_jackpot_value,
                    'time_chip' => $request->time_chip,
                    'room_commn' => $request->room_commission,
                    'serials_commn' => $request->serials_commission,
                    'val_serials' => $request->val_serials,
                    'num_serials' => $request->num_serials
                ]
            );

            if ($ctnPriceCoins) {

                $timestamp = now();

                collect($ctnPriceCoins)->each(function ($ctnPrice, $coin) use ($roomID, $bingoCreatorID, $timestamp) {
                    // Obtener el registro existente
                    $bingoCtnPrice = BingoCtnPrice::getCtnPrice($roomID, $coin, $bingoCreatorID);

                    // Verificar si es necesario actualizar
                    if ($bingoCtnPrice && $bingoCtnPrice->ctn_price != $ctnPrice) {
                        // Construir datos para la actualización
                        $updateData = [
                            'ctn_price' => $ctnPrice,
                            'status' => $ctnPrice ? 1 : 0,
                            'updated_at' => $timestamp,
                        ];
                        // Actualizar el registro existente
                        BingoCtnPrice::where('id', $bingoCtnPrice->id)->update($updateData);
                    } else if (!$bingoCtnPrice && $ctnPrice) {
                        // Crear nuevo registro
                        BingoCtnPrice::create([
                            'room' => $roomID,
                            'bingo_creator_id' => $bingoCreatorID,
                            'coin' => $coin,
                            'ctn_price' => $ctnPrice,
                            'status' => 1
                        ]);
                    }
                });
            }

            DB::commit();

            return redirect()->back()->with('message', 'La información fue actualizada')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Hubo un problema al actualizar la información de la sala: ' . $e->getMessage());
            return redirect()->back()->with('message', 'Hubo un problema al actualizar la información')->with('type', 'warning');
        }
    }

    public function getCtnPriceForCoin($creation, $creatorType, $creatorID, $room = 'RKE')
    {
        // Desencriptar $creatorID
        $creatorID = decrypt($creatorID);

        // Obtener las monedas relevantes según el tipo de creador
        $coins = match ($creatorType) {
            'admin' => Coin::where('id', '!=', 1)->distinct()->pluck('coin'),
            'commerce' => CommerceCountriesCoins::getAvailableCoins($creatorID, 1),
            default => CustomerExchangeRate::getCoinsByCustomer($creatorID),
        };

        if ($room !== 'RKE' && $creation === 'M') {
            // Obtener ID de la sala
            $roomID = BingoRoom::getValueByEncrypted($room, 'id');

            // Parámetros del creador
            $creator = ['type' => $creatorType, 'id' => $creatorID];

            // Obtener ID Bingo Creator
            $bingoCreatorID = BingoCreator::getValueByCreatorRoom($creator, $roomID, 'id');
        }

        // Obtener precios y monedas asociadas
        if ($creation === 'M') {
            $coinsCtnPrice = BingoCtnPrice::getOnlyCoinsCtnPrice($bingoCreatorID ?? '');
        } else {
            $coinsCtnPrice = BingoCtnPriceAutomatic::getOnlyCoinsCtnPrice($room ?? '');
        }

        // Inicializamos un contador para las claves incrementales
        $counter = 0;

        // Obtener información completa de las monedas en `coinsCtnPrice`
        $presentCoinsData = $coinsCtnPrice->mapWithKeys(function ($ctnPrice, $coin) use (&$counter, $creatorType) {
            return [
                $counter++ => [
                    'coin' => $coin,
                    'isoCode' => $creatorType === 'customer'
                        ? $coin
                        : Coin::getValue($coin, 'iso_code'),
                    'ctnPrice' => $ctnPrice,
                ],
            ];
        });

        // Identificar monedas faltantes
        $missingCoins = collect($coins)->diff($coinsCtnPrice->keys());

        // Procesar las monedas faltantes
        $missingCoinsData = $missingCoins->mapWithKeys(function ($missingCoin) use (&$counter, $creatorType) {
            return [
                $counter++ => [
                    'coin' => "$missingCoin",
                    'isoCode' => $creatorType === 'customer'
                        ? $missingCoin
                        : Coin::getValue($missingCoin, 'iso_code'),
                    'ctnPrice' => '', // Precio vacío
                ],
            ];
        });

        // Combinar los datos y estructurarlos en un arreglo con identificador clave
        $arrCoinsCtnPrice = $presentCoinsData->merge($missingCoinsData);

        // Retornar respuesta con los datos
        return response()->json([
            'hasPrice' => count($presentCoinsData) ? true : false,
            'ctnPrice' => $arrCoinsCtnPrice
        ]);
    }

    public function configuration()
    {
        return redirect()->back();
    }

    public function getInfo($id, $starTime = '')
    {
        if ($starTime) {
            $room = BingoRoom::getByEncrypted($id, ['date', 'time']);
            return response()->json($this->convertToUnixTimestamp($room->date, $room->time));
        }

        $id = decrypt($id);

        $room = BingoRoom::select('encrypted', 'jackpot', 'players')->where('id', $id)->firstOrFail();

        $roomConfig = BingoRoomConfig::select(
            'game_mode',
            'date',
            'time',
            'ctn_price',
            'jackpot_type',
            'fixed_jackpot_value',
            'time_chip',
            'room_commn',
            'serials_commn',
            'val_serials',
            'num_serials'
        )->where('room', $id)->firstOrFail();

        $creator = true;

        $bingoRoom = BingoRoom::getById($id, ['bingo_creator', 'commerce']);

        if ($bingoRoom->commerce) {
            $creator = ['type' => 'commerce', 'id' => $bingoRoom->commerce];
            $bingoCreator = BingoCreator::getValueByCreatorRoom($creator, $id);

            $creator = BingoRoom::getValueByBingoCreator($bingoCreator);
        }

        return response()->json([
            'room' => $room,
            'config' => $roomConfig,
            'creator' => $creator ? true : false
        ]);
    }

    public function getInfoByEncrypt($id, $mode = '', $coin = '')
    {
        $roomID = BingoRoom::getValueByEncrypted($id, 'id');

        if (!$roomID) return response()->json(['Error' => 'Sala no encontrada']);

        $room = BingoRoom::select('game_mode', 'unix_date', 'ctn_price', 'coin', 'jackpot_prize')
            ->where('id', $roomID)->firstOrFail();

        $roomConfig = BingoRoomConfig::select('jackpot_type', 'fixed_jackpot_value', 'time_chip', 'room_commn', 'serials_commn', 'val_serials', 'num_serials')
            ->where('room', $roomID)->firstOrFail();

        if ($mode) {
            $creator = BingoRoom::getById($roomID, ['customer', 'commerce']);

            if ($creator->customer) {
                $isLogin = false;
                $custCoin = CustomerExchangeRate::getById(decrypt($coin), ['coin', 'exchange_rate']);
                $coin = $custCoin->coin;
                $exchangeRate = $custCoin->exchange_rate;
            } else {
                $coin = decrypt($coin);
                $exchangeRate = 1;
                $isLogin = ($coin == 1) ? $this->wallet()->usdt : $this->wallet()->balance;
                if ($coin != 1) {
                    $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($creator->commerce, $coin);
                }
                $coin = Coin::getValue($coin, 'symbol');
            }

            $room = [
                'dateTime' => $room->unix_date,
                'coin' => $coin,
                'jackpotAmount' => number_format(($room->jackpot_prize * $exchangeRate), 2, '.'),
                'jackpotType' => $roomConfig->jackpot_type == 1 ? 'Bote' : 'Premio',
                'ctnPrice' => number_format(($room->ctn_price * $exchangeRate), 2, '.')
            ];

            return response()->json(['room' => $room, 'isLogin' => $isLogin]);
        }

        return response()->json(['room' => $room, 'config' => $roomConfig]);
    }

    public function copy($id)
    {
        $existingRoom = BingoRoom::findOrFail(decrypt($id));
        $existingRoomConfig = BingoRoomConfig::where('room', $existingRoom->id)->firstOrFail();

        date_default_timezone_set(config('app.timezone'));

        $boat = ($existingRoom->jackpot_type > 1) ? $existingRoom->fixed_jackpot_value : 0;
        $coin = $existingRoom->coin ?? 1; // ID Coin == 1 (USDT)

        $createData = [
            'game_mode' => $existingRoom->game_mode,
            'status' => 1,
            'creation' => 'M',
            'date' => $existingRoom->date,
            'time' => $existingRoom->time,
            'unix_date' => strtotime("{$existingRoom->date} {$existingRoom->time}"),
            'coin' => $coin,
            'ctn_price' => $existingRoom->ctn_price,
            'jackpot' => $boat,
            'encrypted' => $this->generateUniqueCode('bingo_rooms'),
            'room_type' => $existingRoom->room_type ?? 1
        ];

        $creatorType = $existingRoom->commerce ? 'commerce' : 'customer';
        $creatorID = $existingRoom->{$creatorType};

        $createData[$creatorType] = $creatorID;
        $createData['subsidiary'] = $existingRoom->subsidiary ?? null;

        if ($creatorType === 'customer') {
            $createData['code_uuid'] = $this->getCodeUUID('BingoRoom');
        }

        $creatorType = UserType::getValueByPrefix($creatorType, 'id');

        // Definir $creator
        $creator = ['type' => $creatorType, 'id' => $creatorID];

        $bingoCreator = BingoCreator::getValueByCreatorRoom($creator, $existingRoom->id, 'id');
        $existingRoomCtnPrice = BingoCtnPrice::getAllCtnPrice($bingoCreator);

        DB::beginTransaction();

        try {
            // Obtener la nueva sala
            $newRoom = BingoRoom::create($createData);
            // Obtener ID
            $newRoomID = $newRoom->id;

            // Registar el creador de la sala
            $newBingoCreatorID = BingoCreator::create([
                'room' => $newRoomID,
                'creator_type' => $existingRoom->commerce ? $existingRoom->commerce : $existingRoom->customer,
                'creator_id' => $existingRoom->commerce ? $existingRoom->commerce : $existingRoom->customer,
                'creator_commn' => $existingRoomConfig->room_commn,
                'ctn_price' => $existingRoom->ctn_price,
            ])->id;

            // Actualizar ID BingoCreator
            $newRoom->update(['bingo_creator' => $newBingoCreatorID]);

            BingoRoomConfig::create([
                'room' => $newRoomID,
                'jackpot_type' => $existingRoomConfig->jackpot_type,
                'fixed_jackpot_value' => $existingRoomConfig->fixed_jackpot_value,
                'time_chip' => $existingRoomConfig->time_chip,
                'room_commn' => $existingRoomConfig->room_commn,
                'serials_commn' => $existingRoomConfig->serials_commission,
                'val_serials' => $existingRoomConfig->val_serials,
                'num_serials' => $existingRoomConfig->num_serials,
                //'val_sm_prizes' => $existingRoomConfig->val_sm_prizes,
            ]);

            if ($existingRoomCtnPrice) {

                $timestamp = now();

                $recordsToInsert = collect($existingRoomCtnPrice)
                    ->filter(fn($ctnPrice) => $ctnPrice) // Filtrar solo los elementos con valor
                    ->map(function ($ctnPrice, $coin) use ($newRoomID, $newBingoCreatorID, $timestamp) {
                        return [
                            'room' => $newRoomID,
                            'bingo_creator_id' => $newBingoCreatorID,
                            'coin' => $coin,
                            'ctn_price' => $ctnPrice,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    })->values()->toArray();

                BingoCtnPrice::insert($recordsToInsert);
            }

            DB::commit();
            return redirect()->back()->with('message', 'Sala copiada con exito')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al copiar la sala: ' . $e->getMessage());
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
        // Desencripta el ID y busca el registro correspondiente.
        $bingoRoom = BingoRoom::findOrFail(decrypt($id));

        date_default_timezone_set(config('app.timezone'));

        $publicationDate = "{$bingoRoom->date} {$bingoRoom->time}";
        $unixDate = strtotime($publicationDate);

        if ($unixDate < time()) {
            return redirect()->back()->with('message', 'Fecha/Hora no perimitidas')->with('type', 'warning');
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
            //Eliminar registro del Creador
            BingoCreator::where('room', $id)->delete();
            // Eliminar configuracion de la sala
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

    public function changeStatus($id)
    {
        $id = decrypt($id);
        BingoRoom::where('id', $id)->update(['status' => 1]);
        Session::flash('message', 'Sala actualizada');
        return redirect()->back()->with('type', 'success');
    }

    public function refund($id) // Reembolsar compras de la sala
    {
        $roomID = decrypt($id);

        if (!BingoRoomUser::where('room', $roomID)->exists()) {
            return true;
        }

        $gameID = Games::getValueByName('BINGO');

        foreach (BingoRoomUser::where('room', $roomID)->cursor() as $player) {
            DB::beginTransaction(); // Iniciar transacción por cada iteración

            try {
                $user = $player->user;

                $amountToReturn = BingoCtnsLot::where('room', $roomID)
                    ->where('player_user', $user)->sum('sale_amount');

                // Obtener la cartera del usuario
                $wallet = Wallet::where('user', $user)->first();

                if (!$wallet) {
                    throw new \Exception("Cartera no encontrada para el usuario ID: $user");
                }

                if ($player->coin === 'USDT') {
                    $wallet->usdt += $amountToReturn;
                } else {
                    $wallet->balance += $amountToReturn;
                }

                // Actualizar cartera
                $wallet->save();

                // Insertar historial de jugadas
                PlayHistory::create([
                    'game' => $gameID,
                    'room' => $roomID,
                    'user' => $user,
                    'gain' => $amountToReturn,
                    'action' => 'Retorno'
                ]);

                DB::commit(); // Confirmar transacción de la iteración
            } catch (\Exception $e) {
                DB::rollBack(); // Revertir solo la iteración actual en caso de error
                Log::error("Error al reembolsar al usuario ID $user: " . $e->getMessage());
            }
        }

        // Actualizar estado de la sala después de todas las iteraciones
        BingoRoom::where('id', $roomID)->update(['status' => 5]);

        return redirect()->back()->with('message', 'El bote fue reembolsado')->with('type', 'warning');
    }

    public function actWn(Request $request)
    {
        $playerLink = $request->playerLink;

        if (PlayerLink::where('link', $playerLink)->exists()) {
            $room = PlayerLink::where('link', $playerLink)->value('room');
            $this->handleDraw($room, $playerLink);
            return redirect()->back()->with('message', 'Carton Ganador Generado');
        } else {
            return redirect()->back()->with('message', 'El PlayerLink no existe');
        }
    }

    public function getAutomaticRoomsInfo($creator)
    {
        //  Lista (CRUD) de configuracion de Salas Automaticas
        (new HandleBingoRooms())->checkRoomClosure();

        $creatorType = $creator['type'];
        $creatorID = decrypt($creator['id']);

        switch ($creatorType) {
            case 'admin': // Admin
                break;
            case 'commerce': // Comercio
                $isLogin = true;

                $commerce = Commerce::getById($creatorID, ['coin', 'code_uuid']);

                $GMRoute =  route('bingo.game-rooms-commerce', ['roomType' => 'public', 'coin' => encrypt($commerce->coin)]);

                $moreViewData = ['commerceUUID' => $commerce->code_uuid];

                if ($creatorID == 1) $roomType = Room::getRoomTypes();

                break;
            case 'customer': // Cliente

                $isLogin = $returnBtn = false;

                $token = Customer::getValueById($creatorID, 'token');

                $GMRoute =  route(
                    'bingo.game-rooms-customer',
                    ['customer' => $token, 'user' => $token]
                );

                $moreViewData = ['customer' => true, 'userToken' => $token];

                break;

            default:
                return false;
                break;
        }

        $userType = UserType::getValueByPrefix($creatorType, 'id');

        $automaticRooms = BingoRoomAutomatic::where('creator_type', $userType)
            ->where('creator_id', $creatorID)->whereNot('status', 3)
            ->get([
                'id',
                'time',
                'game_mode',
                'room_type',
                'jackpot_type',
                'fixed_jackpot_value',
                'ctn_price',
                'time_chip',
                'room_commn',
                'serials_commn',
                'val_serials',
                'num_serials',
                'status'
            ]);

        $activeRooms = BingoRoom::where($creator['type'], $creatorID)->whereIn('status', [2, 3])->exists();

        $routeExecuteGenerateBingoRoomsJob = route(
            'bingo.automatic-room.executeGenerateBingoRoomsJob',
            ['creatorType' => $creator['type'], 'creatorID' => $creator['id']]
        );

        $routeGetCtnPriceForCoin = $this->getRouteCtnPriceForCoin();

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Salas de Bingo AUTO',
            'name' => 'bingo',
            'returnBtn' => $returnBtn ?? true,
            'creator' => $creator,
            'rooms' => $automaticRooms,
            'activeRooms' => $activeRooms,
            'routeGenerateBingoRoomsJob' => $routeExecuteGenerateBingoRoomsJob,
            'routeGetCtnPriceForCoin' => $routeGetCtnPriceForCoin,
            'GMRoute' => $GMRoute,
            'roomType' => $roomType ?? [],
            'jackpotType' => JackpotType::all(),
            'gameModes' => GameModes::all()
        ];

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.game.bingo.automatic-room.index', 'data' => $viewData];
    }

    private function allAutomaticRooms($creatorType, $creatorID)
    {
        $creatorID = decrypt($creatorID);

        switch ($creatorType) {
            case 'commerce':
                $creatorID = Commerce::getValueById($creatorID, 'id');
                // Verificar si el comercio existe
                if (!$creatorID) {
                    return response()->json(['message' => 'Token del cliente no existe'], 404);
                }
                break;

            case 'customer':
                $creatorID = Auth::id() ? $creatorID : Customer::getValueByToken($creatorID, 'id');
                // Verificar si el cliente existe
                if (!$creatorID) {
                    return response()->json(['message' => 'Token del cliente no existe'], 404);
                }
                break;

            default:
                # code...
                break;
        }

        $creator = ['type' => $creatorType, 'id' => encrypt($creatorID)];

        // Obtener la información de configuracion de salas automaticas
        $viewData = $this->getAutomaticRoomsInfo($creator);

        // Retornar la vista con la información
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function createAutomaticRoom(Request $request)
    {
        $creator = json_decode($request->creator);
        $creatorType = UserType::getValueByPrefix($creator->type, 'id');
        $creatorID = decrypt($creator->id);

        DB::beginTransaction();

        try {
            $automaticBingoID = BingoRoomAutomatic::create([
                'time' => $request->time,
                'game_mode' => $request->game_mode,
                'room_type' => $request->room_type ?? 1,
                'jackpot_type' => $request->jackpot_type,
                'fixed_jackpot_value' => $request->fixed_jackpot_value,
                'time_chip' => $request->time_chip,
                'ctn_price' => $request->ctn_price,
                'room_commn' => $request->room_commission,
                'serials_commn' => $request->serials_commission,
                'val_serials' => $request->val_serials,
                'num_serials' => $request->num_serials,
                'creator_type' => $creatorType,
                'creator_id' => $creatorID,
            ])->id;

            $ctnPriceCoins = $request->ctn_price_coins;

            if ($ctnPriceCoins) {

                $timestamp = now();

                $recordsToInsert = collect($ctnPriceCoins)
                    ->filter(fn($ctnPrice) => $ctnPrice) // Filtrar solo los elementos con valor
                    ->map(function ($ctnPrice, $coin) use ($automaticBingoID, $timestamp) {
                        return [
                            'automatic_bingo_id' => $automaticBingoID,
                            'coin' => $coin,
                            'ctn_price' => $ctnPrice,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    })->values()->toArray();

                BingoCtnPriceAutomatic::insert($recordsToInsert);
            }

            DB::commit();
            return redirect()->back()->with('message', 'Registro creado con éxito')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear información de una sala automática: ' . $e->getMessage());
            return redirect()->back()->with('msg-error', 'Hubo un error al crear la información');
        }
    }

    public function updateAutomaticRoom(Request $request, $id)
    {
        $id = decrypt($id);

        DB::beginTransaction();

        try {
            BingoRoomAutomatic::where('id', $id)->update([
                'time' => $request->time,
                'game_mode' => $request->game_mode,
                'room_type' => $request->room_type ?? 1,
                'jackpot_type' => $request->jackpot_type,
                'fixed_jackpot_value' => $request->fixed_jackpot_value,
                'time_chip' => $request->time_chip,
                'ctn_price' => $request->ctn_price,
                'room_commn' => $request->room_commission,
                'serials_commn' => $request->serials_commission,
                'val_serials' => $request->val_serials,
                'num_serials' => $request->num_serials,
            ]);

            $ctnPriceCoins = $request->ctn_price_coins;

            if ($ctnPriceCoins) {

                $timestamp = now();

                collect($ctnPriceCoins)->each(function ($ctnPrice, $coin) use ($id, $timestamp) {
                    // Obtener el registro existente
                    $bingoCtnPrice = BingoCtnPriceAutomatic::getCtnPrice($coin, $id);

                    // Verificar si es necesario actualizar
                    if ($bingoCtnPrice && $bingoCtnPrice->ctn_price != $ctnPrice) {
                        // Construir datos para la actualización
                        $updateData = [
                            'ctn_price' => $ctnPrice,
                            'status' => $ctnPrice ? 1 : 0,
                            'updated_at' => $timestamp,
                        ];
                        // Actualizar el registro existente
                        BingoCtnPriceAutomatic::where('id', $bingoCtnPrice->id)->update($updateData);
                    } else if (!$bingoCtnPrice && $ctnPrice) {
                        // Crear nuevo registro
                        BingoCtnPriceAutomatic::create([
                            'automatic_bingo_id' => $id,
                            'coin' => $coin,
                            'ctn_price' => $ctnPrice,
                            'status' => 1
                        ]);
                    }
                });
            }

            DB::commit();
            return redirect()->back()->with('message', 'Registro actualizado con éxito')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear información de una sala automática: ' . $e->getMessage());
            return redirect()->back()->with('msg-error', 'Hubo un error al actualizar la información');
        }
    }

    public function getInfoAutomaticRoom($id)
    {
        $id = decrypt($id);

        $room = BingoRoomAutomatic::select(
            'id',
            'time',
            'game_mode',
            'room_type',
            'jackpot_type',
            'fixed_jackpot_value',
            'ctn_price',
            'time_chip',
            'room_commn',
            'serials_commn',
            'val_serials',
            'num_serials',
            'status'
        )->where('id', $id)->firstOrFail();

        return response()->json(['room' => $room]);
    }

    public function changeStatusAutomaticRoom($id)
    {
        $id = decrypt($id);

        DB::beginTransaction();

        try {
            $roomAutomaticStatus = (int)!BingoRoomAutomatic::getValue($id, 'status');

            BingoRoomAutomatic::where('id', $id)->update(['status' => $roomAutomaticStatus]);

            DB::commit();

            return redirect()->back()->with('message', 'Status actualizado')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('msg-error', 'Ha ocurrido un error');
        }
    }

    public function deleteAutomaticRoom($id)
    {
        $id = decrypt($id);

        DB::beginTransaction();

        try {
            BingoRoomAutomatic::where('id', $id)->update(['status' => 3]);
            DB::commit();
            return redirect()->back()->with('message', 'La configuración ha sido eliminada')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('msg-error', 'Ha ocurrido un error');
        }
    }

    public function executeGenerateBingoRoomsJob($creatorType, $creatorID)
    {
        $creatorID = decrypt($creatorID);

        switch ($creatorType) {
            case 'admin':
                //$existsCreator = Admin::existsValue('id', $creatorID);
                break;
            case 'commerce':
                $existsCreator = Commerce::existsValue('id', $creatorID);
                break;
            case 'customer':
                $existsCreator = Customer::existsValue('id', $creatorID);
                break;
            default:
                $existsCreator = false;
                break;
        }

        if (!$existsCreator) {
            return redirect()->back()->with('message', 'El creador no existe')->with('type', 'warning');
        }

        $creatorType = UserType::getValueByPrefix($creatorType, 'id');

        $creator = ['type' => $creatorType, 'id' => $creatorID];

        $service = new AutoGenerateBingoRooms();
        $response = $service->createBingoRooms($creator);

        if ($response) {
            if (!isset($response['Info'])) {
                $typeMessage = 'success';
                $message = 'Salas creadas correctamente';
            } else {
                $typeMessage = 'info';
                $message = 'No hay configuraciones activas';
            }
        } else {
            $typeMessage = 'warning';
            $message = 'Ha ocurrido un error';
        }
        return response()->json(['type' => $typeMessage, 'message' => $message]);
    }

    public function regenerateDraw(Request $request)
    {
        // FUNCION PARA REGENERAR SORTEO
        // USAR FUNCION SOLO PARA TESTES

        // Declarar variables
        $roomEncrypted = $request->room;
        $room = BingoRoom::getValueByEncrypted($roomEncrypted, 'id');
        $keyword = $request->keyword;

        $draw = BingoDraw::where('room', $room)->count() === 75;

        // Validar condiciones iniciales
        if (!$draw) {
            $this->handleDraw($room);

            return response()->json([
                'type' => 'success',
                'message' => 'Proceso efectuado con éxito'
            ]);
        }

        if ($keyword !== 'RK3_T357E*2K25') {
            return response()->json([
                'type' => 'warning',
                'message' => 'Ha ocurrido un error en la solicitud'
            ]);
        }

        date_default_timezone_set(config('app.timezone'));

        // Obtener datos iniciales
        $symbols = BingoDrawCode::where('room', $room)->pluck('sym', 'num');
        $draws = BingoDraw::where('room', $room)->get();
        $currentTimestamp = now()->addMinutes(1)->timestamp;
        $timeIncrement = 8;

        // Eliminar Ficha Ganadora
        BingoDraw::where('room', $room)->where('win', 1)->update(['win' => 0]);

        $chips = [];
        $updates = [];

        if (!count($symbols)) {
            $this->encryptDraw($room);
        }

        foreach ($draws as $index => $draw) {
            do {
                $rnd = mt_rand(1, 75);
            } while (isset($chips[$rnd]));

            // Codificar el nuevo número
            $numEncoded = implode('', array_map(
                fn($digit) => $symbols[$digit == '0' ? 10 : $digit] ?? '',
                str_split($rnd)
            ));

            // Calcular nuevo tiempo
            $newTime = $currentTimestamp + ($index * $timeIncrement);

            $updates[] = [
                'id' => $draw->id,
                'chip' => $rnd,
                'code' => $numEncoded,
                'time' => $newTime,
                'updated_at' => now()
            ];

            $chips[$rnd] = true;

            // Cambiar incremento después del primer chip
            $timeIncrement = 9;
        }

        // Realizar transacción
        DB::beginTransaction();

        try {
            foreach ($updates as $update) {
                BingoDraw::where('id', $update['id'])->update([
                    'chip' => $update['chip'],
                    'code' => $update['code'],
                    'time' => $update['time'],
                    'updated_at' => $update['updated_at'],
                ]);
            }

            // Definir Obtener Ficha Ganadora
            $this->chipWin($room);

            // Ajustar status de la sala
            BingoRoom::where('id', $room)->update(['status' => 3]);

            DB::commit();

            return response()->json([
                'type' => 'success',
                'message' => 'Proceso efectuado con éxito'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al regenerar sorteo [' . $room . ']: ' . $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'Hubo un error durante el proceso de regeneración'
            ]);
        }
    }
}
