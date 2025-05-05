<?php

namespace App\Http\Controllers;

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
use App\Models\BingoPrizeCtns;
use App\Models\BingoPrizeCtnsPayment;
use App\Models\BingoRoomAutomatic;
use App\Models\BingoTypesPlays;
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
use App\Models\TimeZone;
use App\Models\User;
use App\Models\UserPurchaseCodes;
use App\Models\UserType;
use App\Models\UUIDCodes;
use App\Services\Customer\HandleTransactions;
use App\Services\Game\Bingo\AutoGenerateBingoRooms;
use App\Services\Game\Bingo\HandleBingoRooms;
use App\Traits\BingoTrait;
use App\Traits\CustomerTrait;
use App\Traits\Filters;
use App\Traits\GameTrait;
use App\Traits\RouteRKE;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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

    public function getDateTimeRooms($creatorUser)
    {
        $rooms = BingoRoom::with('Config:room,unix_date')->select('id', 'encrypted', 'status')
            ->where('creator_user', decrypt($creatorUser))->whereIn('status', [2, 3])->get();

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

        $creatorUser = $creator['userId'];

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
        $roomType = $data['roomType'] === 'private' ? 'PV' : 'PB';

        $rooms = BingoRoom::with([
            'Config' => function ($query) {
                $query->select('room', 'game_mode', 'jackpot_type', 'date', 'time', 'coin', 'ctn_price');
            },
            'CtnPrice' => function ($query) use ($creatorUser, $coin) {
                $query->where('creator_user', $creatorUser)
                    ->where('coin', $coin);
            },
            'PlayersRoom' => function ($query) use ($playerColumn, $roomUser) {
                $query->select('id', $playerColumn, 'room', 'ctns')
                    ->where($playerColumn, $roomUser);
            }
        ])
            ->select([
                'id',
                'status',
                'players',
                'jackpot_prize',
                'ctns',
                'encrypted'
            ])
            ->where('creator_user', $creatorUser)
            ->where('room_type', $roomType)
            ->whereHas('Config', function ($query) {
                $query->whereIn('game_mode', [1, 3])
                    ->whereNotNull('room');
            })
            ->whereIn('status', [2, 3])
            ->orderByRaw("(SELECT unix_date FROM bingo_rooms_config WHERE bingo_rooms_config.room = bingo_rooms.id LIMIT 1)")
            ->get();

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
            'creatorUser' => encrypt($creatorUser),
            'rooms' => $rooms,
            'imageBingoRules' => $this->getImageBingoRules($creatorUser),
            'bingoRules' => $this->getBingoRules($creatorUser),
            'dateTimeRooms' => $this->getDateTimeRooms(encrypt($creatorUser))
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

        $creator = [
            'type' => 'commerce',
            'id' => $commerce,
            'userId' => Auth::id()
        ];

        $viewData = self::getGamesRoomsView(
            $creator,
            ['coin' => decrypt($coin), 'roomType' => $roomType]
        );


        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function gamesRoomsCustomer($customerID, $userToken, $coin = '')
    {
        // Si el token de usuario es diferente de 'available-rooms' 
        if ($userToken !== 'available-rooms') {
            $customer = $this->validateCustomer($customerID, $userToken);

            // Verifica si hubo error en la validación del cliente
            if (isset($customer['Error'])) {
                return response()->json(['message' => $customer['Error']], 404);
            }

            $customerUser = CustomerUser::getByCustomerID($customerID, $userToken, ['id', 'coin']);
            $coin = CustomerExchangeRate::getCustomerCoinValue($customer, $customerUser->coin, 'id');

            $player = $customerUser->id;
        } else {
            $customer = Customer::getValueByCustomerID($customerID, 'id');
            $coin = decrypt($coin);
            $player = false;
        }

        $creator = [
            'type' => 'customer',
            'id' => $customer,
            'userId' => Customer::getValueById($customer, 'user')
        ];

        // Obtiene la información de vista del cliente
        $viewData = self::getGamesRoomsView($creator, [
            'roomType' => 'private',
            'userToken' => $userToken,
            'player' => $player,
            'coin' => $coin
        ]);

        // Retorna la vista con la información
        return $this->handleViewData($viewData, $creator['type']);
    }

    private function handleCartonByCreator($creator, $roomCtnPrice, $creatorCtnPrice, $data, $request)
    {
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
                    $data['playerLink'] = $player ?? null;
                }
            }
        }

        return  $data;
    }

    private function handleCartonBySeller($data, $request)
    {
        $seller = Seller::getByUserId(Auth::id(), ['id', 'commerce']);
        $commerce = $seller->commerce;

        $creator = [
            'type' => 'commerce',
            'id' => $commerce,
            'userId' => Commerce::getValueById($commerce, 'user')
        ];

        $data['creator'] = $creator;

        $data['commerce'] = $commerce;
        $data['seller'] = $seller->id;
        $data['country'] = $request->country;
        $data['coin'] = Coin::getValueByCountry($request->country, 'id');
        $data['playerPhone'] = $request->phone ?? null;
        $data['playerName'] = $request->name ?? null;
        $data['playerLastname'] = $request->last_name ?? null;

        $data['exchangeRate'] = CommerceExchangeRate::getCommerceCoinValue($commerce, $data['coin']);

        return $data;
    }

    public function generateCarton(Request $request)
    {
        $room = BingoRoom::with('Config:room,jackpot_type,ctn_price')
            ->select('id', 'room_type', 'status')
            ->where('encrypted', $request->room)->first();

        if (!$room || ($room && $room->status > 2)) {
            return redirect()->back()->with('message', 'Sala no encontrada')->with('type', 'warning');
        }

        $data = [
            'room' => $room->id,
            'typeSale' => $request->type_sale,
            'jackpotType' => $room->Config->jackpot_type,
            'numOfCtns' => $request->num_ctns ?? 1
        ];

        $roomCtnPrice = $room->Config->ctn_price;
        $creatorType = $request->creator_type ?? false;

        if ($creatorType) {

            $creatorID = decrypt($request->creator_id);
            $creatorUser = '';

            switch ($creatorType) {
                case 'admin':
                    # code...
                    break;
                case 'commerce':
                    $creatorUser = Commerce::getValueById($creatorID, 'user');
                    break;
                case 'customer':
                    $creatorUser = Customer::getValueById($creatorID, 'user');
                    break;
                default:
                    # code...
                    break;
            }

            $creator = [
                'type' => $creatorType,
                'id' => $creatorID,
                'userId' => $creatorUser
            ];

            // Adicionar datos al conjunto
            $data['creator'] = $creator;
            $data['isLogin'] = $request->is_login ?? false;
            $data['coin'] = decrypt($request->coin);

            $creatorCtnPrice = BingoCtnPrice::getValueCtnPrice($room->id, $data['coin'], $creator['userId']);

            $response = $this->handleCartonByCreator($creator, $roomCtnPrice, $creatorCtnPrice, $data, $request);

            if (isset($response['Error'])) {
                return response()->json(['type' => 'warning', 'message' => $response['Error']]);
            }

            $data = $response;
        } else {
            $data = $this->handleCartonBySeller($data, $request);
            $creatorCtnPrice = BingoCtnPrice::getValueCtnPrice($room->id, $data['coin'], $data['creator']['userId']);
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

            if ($creatorType && isset($creator)) {
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

            $responseCartonStore = $this->getGameLinkByPlayer($data['playerLink']);
            Session::flash('message', 'Proceso efectuado con éxito');
            Session::flash('playerLink', $responseCartonStore);

            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar la solicitud generateCarton(): ' . $e->getMessage());
            $message = 'Error al procesar la solicitud';
            $response = isset($creator)
                ? response()->json(['type' => 'warning', 'message' => $message])
                : redirect()->back()->with(['type' => 'warning', 'message' => $message]);

            return $response;
        }
    }

    private function getImageBingoRules($creatorUser)
    {
        $game = GameRules::getByGame('BINGO', $creatorUser, ['image', 'encrypted']);
        $image = false;

        if ($game) {
            $image = "bingo-rules/$game->encrypted/$game->image";
        }

        return $image;
    }

    public function getBingoRules($creatorUser)
    {
        $rules = GameRules::getValueByGame('BINGO', $creatorUser);

        return $rules ?: view("includes.info.game.bingo");
    }

    private function getGameInfo($creator, $data = [])
    {
        $creatorUser = $creator['userId'];

        switch ($creator['type']) {

            case 'customer':
                $creatorID = $creator['id'];

                // Obtener informacion de Sala
                $room = BingoRoom::where('creator_user', $creatorUser)
                    ->where('encrypted', $data['room'])
                    ->whereIn('status', [2, 3, 4])->exists();

                // Si la sala no existe 
                if (!$room) return ['error' => 'Sala no disponible'];

                $roomID = $room->id;
                $isLogin = false;

                $customer = Customer::getById($creatorID, ['name', 'customer_id', 'logo', 'token']);
                $customerID = $customer->customer_id;
                $userToken = $customer->token;

                $moreViewData = ['custLogo' => "$userToken/$customer->logo"];

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
                    ['customer' => $customerID, 'user' => $userToken]
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

                    // Obtener informacion de Sala
                    $room = BingoRoom::where('id', $roomID)->whereIn('status', [2, 3, 4])->exists();

                    if (!$room) return ['error' => 'Sala no disponible'];

                    $isLogin = false;

                    $roomUser =  BingoRoomUser::getValueByColumn($roomID, 'player_link', $playerLink->id, 'id');

                    $coin = Coin::getCoinByCountry($playerLink->country, ['id', 'symbol']);
                    $coinID = $coin->id;
                    $coinSym = $coin->symbol;

                    $coinsAvailable = Coin::getById($coinID, ['id', 'symbol', 'long_name']);
                } else {
                    $roomID = BingoRoom::getValueByEncrypted($data['room'], 'id');

                    // Obtener informacion de Sala
                    $room = BingoRoom::where('id', $roomID)->whereIn('status', [2, 3, 4])->exists();

                    if (!$room) return ['error' => 'Sala no disponible'];

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

                $moreViewData = [
                    'player' => isset($roomUser) ? encrypt($roomUser) : false,
                    'saleLink' => true,
                    'coinsAvailable' => $coinsAvailable ?? []
                ];

                $bingoCreatorUser = BingoCreator::getValueByCreatorRoom($creator, $roomID, 'creator_user');
                // Definir precio del carton 
                $bingoCtnPrice = BingoCtnPrice::getValueCtnPrice($roomID, $coinID, $bingoCreatorUser)
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

        $room = BingoRoom::with('Config:room,date,time,jackpot_type,pct_types_plays,types_plays')
            ->select(['id', 'status', 'encrypted'])->where('id', $roomID)->first();

        if (!in_array($room->status, [2, 3, 4])) return ['error' => 'Sala no disponible'];

        date_default_timezone_set(config('app.timezone'));

        $vanDraw = ($room->status > 2) ?
            BingoDraw::select('id', 'chip')->where(['room' => $roomID, 'status' => 3])->first() : [];

        $sort = ($room->status == 3) ?
            BingoDraw::where('room', $roomID)->where('id', '<=', $vanDraw->id)
            ->where('time', '>', time())->get(['code', 'time']) : [];

        $chipsRest = ($room->status == 3) ?
            BingoDraw::select('chip')->where('room', $roomID)->where('time', '>', time())
            ->where('id', '<=', $vanDraw->id)->orderBy('chip')->pluck('chip')->sort()->toArray() : [];

        $codeEncrypt = ($room->status == 3) ?
            BingoDrawCode::select('sym')->where('room', $roomID)
            ->pluck('sym')->toArray() : [];

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
            'jackpotType' => $room->Config->jackpot_type,
            'coinSym' => $coinSym,
            'jackpot' => $this->convertPrizeValue($roomID, $coinID),
            'startTime' => $this->convertToUnixTimestamp($room->Config->date, $room->Config->time),
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
            'imageBingoRules' => $this->getImageBingoRules($creatorUser),
            'bingoRules' => $this->getBingoRules($creatorUser),
            'closingRoute' => $closingRoute
        ];

        if ($room->status == 2) $viewData['coinID'] = encrypt($coinID);

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.game.bingo.in-game', 'data' => $viewData];
    }

    public function inGame($player, $room = '')
    {
        // Vista para el juego de la venta conjunta de todos los comercios
        $player = PlayerLink::getByLink($player, ['room', 'creator_user', 'link', 'coin']);

        if (!$player) abort(404); // Retorna un error 404 Not Found

        $creatorUser = $player->creator_user;
        $userCreator = User::getCreatorIDByUserType($creatorUser, '', true);

        if (!$player) abort(404); // Retorna un error 404 Not Found

        $creator = [
            'type' => $userCreator['type'],
            'id' => $userCreator['id'],
            'userId' => $creatorUser
        ];

        $viewData = self::getGameInfo($creator, [
            'playerLink' => $player->link,
            'room' => $player->room,
            'coin' => $player->coin
        ]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function inGameCustomer($customerID, $userToken, $room)
    {
        // Validar Informacion de cliente-user y crear variabales de session
        $response = $this->validateCustomer($customerID, $userToken, 'in-game');

        if (isset($response['Error'])) {
            return response()->json(['message' => $response['Error']], 404);
        }

        $creator = [
            'type' => 'customer',
            'id' => $response,
            'userId' => Customer::getValueById($response, 'user')
        ];

        // Obtener informacion de la vista del cliente
        $viewData = self::getGameInfo($creator, ['room' => $room, 'userToken' => $userToken]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function outGame($uuidCode, $room = '', $coin = '')
    {
        // Vista del el juego solo para comprar [TODOS]
        $code = UUIDCodes::getByCode($uuidCode);

        $creatorUser = $code->user;

        $creator = [
            'type' => UserType::getValueById($code->user_type, 'prefix'),
            'id' => User::getCreatorIDByUserType($creatorUser),
            'userId' => $creatorUser
        ];

        $viewData = self::getGameInfo($creator, ['room' => $room, 'coin' => decrypt($coin)]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function outGameCommerce($commerce, $room, $coin)
    {
        // Vista del el juego para un comercio especifico solo para comprar
        $commerce = Commerce::getValueByCodeUUID($commerce, 'id') ?? decrypt($commerce);

        // Redireccionar al Login si no hay coincidencia    
        if (!$commerce) return redirect()->back();

        $creator = [
            'type' => 'commerce',
            'id' => $commerce,
            'userId' => Commerce::getValueById($commerce, 'user')
        ];

        $viewData = self::getGameInfo($creator, ['room' => $room, 'coin' => decrypt($coin)]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function outGameCustomer($customer, $room, $coin)
    {
        // Vista del el juego para un cliente especifico para comprar y/o visualizar
        $customer = Customer::getById(decrypt($customer), ['id', 'user']);

        // Redireccionar a la vista anterior   
        if (!$customer) return redirect()->back();

        $creator = ['type' => 'customer', 'id' => $customer->id, 'userId' => $customer->user];

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

        $chipVanID = BingoDraw::where('room', $room->id)->where('status', 3)->value('id');

        date_default_timezone_set(config('app.timezone'));

        if ($chipVanID) {
            // Realizamos una única consulta para obtener todas las fichas en las condiciones especificadas
            $allChips = array_map('intval', BingoDraw::select('chip')->where('room', $room->id)
                ->where('id', '<=', $chipVanID)->where('time', '<=', time())
                ->orderByDesc('id')->pluck('chip')->toArray());

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

    private function getInfoRooms($creatorUser, $roomType, $coin, $otherCommerce = false)
    {
        $gameID = Games::getValueByName('BINGO');
        $roomType = $roomType === 'private-rooms' ? 'PV' : 'PB';
        $operator = $otherCommerce ? '<>' : '=';

        $relations = [
            'Config.JackpotType',
            'Config:room,game_mode,jackpot_type,ctn_price,date,time,unix_date',
            'Config.GameMode:id,description',
            'PlayersRoom' => fn($query) => $query->where('winner', '>', 0),
            'GameExchangeRate' => fn($query) => $query->where('coin', $coin)->where('game', $gameID),
        ];

        if ($otherCommerce) {
            $relations['Creator'] = fn($q) => $q->select('id', 'room')
                ->where('creator_user', $creatorUser)->where('status', 1);

            $relations['CtnsLot'] = fn($q) => $q->select('id', 'room', 'exchange_rate', 'sale_amount')
                ->where('creator_user', $creatorUser)->where('status', 1);
        } else {
            $relations['Creator'] = fn($query) => $query->select('id', 'room');
        }

        $query = BingoRoom::with($relations)
            ->where('creator_user', $operator, $creatorUser)
            ->where('room_type', $roomType);

        if ($otherCommerce) {
            $query->whereHas('Creator', function ($q) use ($creatorUser) {
                $q->where('creator_user', $creatorUser)->where('status', 1);
            });
        }

        return $query->orderBy('status')
            ->orderByRaw("(SELECT CONCAT(date, ' ', time) FROM bingo_rooms_config WHERE bingo_rooms_config.room = bingo_rooms.id LIMIT 1)")
            ->get();
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
                    'uuidCode' => Commerce::getValueById($creatorID, 'code_uuid'),
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

                $customer = Customer::getById($creatorID, ['customer_id', 'token']);

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
                    [
                        'customer' => $customer->customer_id,
                        'user' => 'available-rooms',
                        'coin' => encrypt($coin)
                    ]
                );

                $gameID = Games::getValueByName('BINGO');

                $customerGameID = CustomerGames::getByGameID($creatorID, $gameID) ?? null;

                $moreViewData = [
                    'customer' => true,
                    'userToken' => $customer->token,
                    'customerGameID' => $customerGameID
                ];

                break;

            default:
                return false;
                break;
        }

        $rooms = $this->getInfoRooms($creator['userId'], $roomType, $coin);

        $bingoPlays = BingoTypesPlays::getAllByUser($creator['userId'], ['id', 'name'], 1);

        $timeZoneID = User::getValueById($creator['userId'], 'time_zone');
        $timeZone = TimeZone::getValueById($timeZoneID);

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Salas de Bingo',
            'subtitle' => $view === 'private' ? 'Privadas' : 'Publicas',
            'view' => strtoupper($view),
            'name' => 'bingo',
            'returnBtn' => $returnBtn ?? true,
            'routeWithCoin' => $routeWithCoin,
            'routeRKE' => $this->getRouteRKE(),
            'creator' => $creator,
            'coins' => $coins,
            'coin' => $coin,
            'coinSym' => $coinSym,
            'exchangeRate' => $exchangeRate ?? 1,
            'roomType' => $view === 'private' ? 'PV' : 'PB',
            'bingoPlays' => $bingoPlays,
            'timeZone' => $timeZone,
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

    public function allRooms($roomType, $creatorType, $creatorID = '', $coin = '')
    {
        $roomTypes = ['automatic-rooms', 'private-rooms', 'public-rooms', 'other-public-rooms'];

        if (!in_array($roomType, $roomTypes)) {
            return redirect()->back();
        }

        if ($roomType === 'automatic-rooms') {
            return $this->allAutomaticRooms($creatorType, $creatorID);
        } else if ($roomType === 'other-public-rooms') {
            return $this->allOtherPublicRooms($creatorType, $creatorID, $coin);
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
            'userId' => Auth::id()
        ];

        $viewData = $this->getGameRoomsInfo($creator, decrypt($coin), $roomType);

        // Retorna la vista con la información
        return $this->handleViewData($viewData, $creator['type']);
    }

    private function allCustomerRooms($roomType, $creatorID = '', $coin = '')
    {
        $customer = Customer::getByUserId(Auth::id(), ['id', 'user']);

        if (!$customer) {
            $isLogin = false;
            $customerID = Customer::getValueByToken($creatorID, 'id');

            // Verificar si el cliente existe
            if (!$customerID) {
                return response()->json(['message' => 'Token del cliente no existe'], 404);
            }
        } else {
            $isLogin = true;
            $customerID = $customer->id;
        }

        // Si no se proporciona moneda, obtener la primera disponible
        if (!$coin) {

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

        $creator = [
            'type' => 'customer',
            'id' => encrypt($customerID),
            'userId' => $customer->user
        ];

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

        if (!in_array($creatorType, $allowedPrefixes)) {
            return redirect()->back()->with('msg-error', 'No tienes permiso para crear una sala');
        }

        $userID = Auth::id();
        $fixedJackpotValue = $request->fixed_jackpot_value;

        $boat = $request->jackpot_type > 1 ? $fixedJackpotValue : 0;
        $coin = $request->coin ?? 1; // ID Coin == 1 (USDT)

        $model = 'BingoRoom';

        $createData = [
            'encrypted' => $this->generateUniqueCode($model),
            'status' => 1,
            'room_type' => $request->room_type,
            'creator_user' => $userID,
            'jackpot' => $boat,
            'code_uuid' => $this->getCodeUUID($model)
        ];

        date_default_timezone_set(config('app.timezone'));

        DB::beginTransaction();

        try {

            $roomID = BingoRoom::create($createData)->id;

            $dataConfig = [
                'room' => $roomID,
                'creation' => 'M',
                'creator_user' => $userID,
                'game_mode' => $request->game_mode,
                'date' => $request->date,
                'time' => $request->time,
                'unix_date' => strtotime("{$request->date} {$request->time}"),
                'coin' => $coin,
                'ctn_price' => $request->ctn_price,
                'room_commn' => $request->room_commn,
                'jackpot_type' => $request->jackpot_type,
                'fixed_jackpot_value' => $request->fixed_jackpot_value,
                'time_chip' => $request->time_chip,
                'pct_serials' => $request->pct_serials,
                'num_serials' => $request->num_serials,
                'serials_commn' => $request->serials_commn
            ];

            $typesOfPlays = $request->bingo_plays;

            if ($typesOfPlays) {
                $dataConfig['pct_types_plays'] = $request->pct_bingo_plays;
                $dataConfig['types_plays'] = implode(';', $typesOfPlays);;
            }

            BingoRoomConfig::create($dataConfig);

            $creatorType = UserType::getValueByPrefix($creatorType, 'id');

            // Registar el creador de la sala
            BingoCreator::create([
                'room' => $roomID,
                'creator_user' => $userID,
                'creator_type' => $creatorType,
                'creator_id' => $creatorID,
                'creator_commn' => $request->room_commn,
                'ctn_price' => $request->ctn_price,
            ]);

            $ctnPriceCoins = $request->ctn_price_coins;

            if ($ctnPriceCoins) {
                $this->handleCtnPriceCoins('CREATE', $ctnPriceCoins, $roomID, $userID);
            }

            DB::commit();
            return redirect()->back()->with('message', 'Sala creada con éxito')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear la sala: ' . $e->getMessage());
            return redirect()->back()->with(['type' => 'warning', 'message' => 'Error al crear la sala']);
        }
    }

    private function handleCtnPriceCoins($action, $ctnPriceCoins, $roomID, $creatorUser)
    {
        $timestamp = now();

        foreach ($ctnPriceCoins as $coin => $ctnPrice) {
            if (!$ctnPrice) {
                continue; // Ignora valores vacíos o falsy
            }

            $data = [
                'room' => $roomID,
                'creator_user' => $creatorUser,
                'coin' => $coin,
                'ctn_price' => $ctnPrice,
                'status' => 1,
                'updated_at' => $timestamp,
            ];

            if ($action === 'CREATE') {
                $data['created_at'] = $timestamp;
                $batch[] = $data;
            } else {
                $existing = BingoCtnPrice::getCtnPrice($roomID, $coin, $creatorUser);

                if ($existing) {
                    if ($existing->ctn_price != $ctnPrice) {
                        $existing->update($data);
                    }
                } else {
                    $data['created_at'] = $timestamp;
                    BingoCtnPrice::create($data);
                }
            }
        }

        if ($action === 'CREATE' && !empty($batch ?? [])) {
            BingoCtnPrice::insert($batch);
        }
    }

    public function update(Request $request, $id)
    {
        $roomID = decrypt($id);
        $room = BingoRoom::findOrFail($roomID);

        $creatorUser = $this->getUserData()['userId'];

        $ctnPriceCoins = $request->ctn_price_coins;

        if (($ctnPriceCoins || $request) && $room->creator_user <> $creatorUser) {
            DB::beginTransaction();
            try {
                if ($ctnPriceCoins) {
                    $this->handleCtnPriceCoins('UPDATE', $ctnPriceCoins, $roomID, $creatorUser);
                }

                $bingoCreator = BingoCreator::select('id', 'creator_commn', 'ctn_price')
                    ->where('room', $roomID)->where('creator_user', $creatorUser)->first();

                if ($bingoCreator) {
                    $updates = [];

                    if ($bingoCreator->creator_commn != $request->room_commn) {
                        $updates['creator_commn'] = $request->room_commn;
                    }

                    if ($bingoCreator->ctn_price != $request->ctn_price) {
                        $updates['ctn_price'] = $request->ctn_price;
                    }

                    if ($updates) {
                        $bingoCreator->update($updates);
                    }
                }

                DB::commit();
                return redirect()->back()->with('message', 'Proceso exitoso')->with('type', 'success');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al actualizar el precio del carton respecto a las monedas: ' . $e->getMessage());
                return redirect()->back()->with(['type' => 'warning', 'message', 'Hubo un problema al actualizar la información']);
            }
        }

        date_default_timezone_set(config('app.timezone'));

        $newDate = $request->date;
        $newTime = $request->time;
        $unixDate = strtotime("{$newDate} {$newTime}");

        if ($unixDate < time()) {
            return redirect()->back()->with('message', 'Fecha/Hora no permitidas')->with('type', 'warning');
        }

        $fixedJackpotValue = $request->fixed_jackpot_value;

        $boat = $request->jackpot_type > 1 ? $fixedJackpotValue : 0;
        $coin = $request->coin ?? 1;

        $updateDataRoom = ['room_type' => $request->room_type, 'jackpot' => $room->jackpot + $boat];

        $updateDataConfig = [
            'date' => $newDate,
            'time' => $newTime,
            'unix_date' => $unixDate,
            'coin' => $coin,
            'ctn_price' => $request->ctn_price,
            'room_commn' => $request->room_commn,
            'jackpot_type' => $request->jackpot_type,
            'fixed_jackpot_value' => $request->fixed_jackpot_value,
            'time_chip' => $request->time_chip,
            'pct_serials' => $request->pct_serials,
            'num_serials' => $request->num_serials,
            'serials_commn' => $request->serials_commn
        ];

        $typesOfPlays = $request->bingo_plays;

        if ($typesOfPlays) {
            $updateDataConfig['pct_types_plays'] = $request->pct_bingo_plays;
            $updateDataConfig['types_plays'] = implode(';', $typesOfPlays);;
        }

        // Solo añadir 'game_mode' si el jackpot es 0
        if ($room->jackpot == 0) {
            $updateDataConfig['game_mode'] = $request->game_mode;
        }

        DB::beginTransaction();

        try {
            // Actualizar el bingo con los datos construidos
            $room->update($updateDataRoom);

            BingoRoomConfig::where('room', $roomID)->update($updateDataConfig);

            $bingoCreator = BingoCreator::where('room', $roomID)->firstOrFail();

            $bingoCreator->update([
                'creator_commn' => $request->room_commn,
                'ctn_price' => $request->ctn_price,
            ]);

            $creatorUser = $room->creator_user;

            if ($ctnPriceCoins) {
                $this->handleCtnPriceCoins('UPDATE', $ctnPriceCoins, $roomID, $creatorUser);
            }

            DB::commit();

            return redirect()->back()->with('message', 'La información fue actualizada')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error a al actualizar la información de la sala: ' . $e->getMessage());
            return redirect()->back()->with(['type' => 'warning', 'message', 'Error al actualizar la información']);
        }
    }

    public function getCtnPriceForCoin($creation, $creatorType, $creatorID, $room = '')
    {
        // Desencriptar $creatorID
        $creatorID = decrypt($creatorID);

        // Obtener las monedas relevantes según el tipo de creador
        $coins = match ($creatorType) {
            'admin' => Coin::where('id', '!=', 1)->distinct()->pluck('coin'),
            'commerce' => CommerceCountriesCoins::getAvailableCoins($creatorID, 1),
            default => CustomerExchangeRate::getCoinsByCustomer($creatorID),
        };

        $coinsCtnPrice = collect();

        if ($room) {
            if ($creation === 'M') {
                // Obtener ID de la sala
                $roomID = BingoRoom::getValueByEncrypted($room, 'id');

                // Parámetros del creador
                $creator = ['type' => $creatorType, 'id' => $creatorID];

                // Obtener ID Bingo Creator-User
                $bingoCreatorUser = BingoCreator::getValueByCreatorRoom($creator, $roomID, 'creator_user');

                $coinsCtnPrice = BingoCtnPrice::getOnlyCoinsCtnPrice($roomID, $bingoCreatorUser ?? false);
            } else {
                $coinsCtnPrice = BingoCtnPriceAutomatic::getOnlyCoinsCtnPrice($room ?? '');
            }
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

    public function configuration($userToken = false)
    {
        if ($userToken) {
            $userType = 'customer';
            $userToken = true;
            $user = Customer::getValueByToken($userToken, 'user');
        } else {
            $user = Auth::id();
            $userTypeID = User::getValueById($user, 'user_type');
            $userType = UserType::getValueById($userTypeID, 'prefix');
        }

        if (!in_array($userType, ['admin', 'commerce', 'customer'])) {
            return redirect()->back();
        }

        $game = GameRules::getByGame('BINGO', $user, ['id', 'rules', 'encrypted']);
        $rulesID = '';
        $gameRules = '';

        if ($game) {
            $rulesID = encrypt($game->id);
            $gameRules = $game->rules;
        }

        $viewData = [
            'layout' => !$userToken ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Bingo Configuración',
            'userToken' => $userToken,
            'rulesID' => $rulesID,
            'gameRules' => $gameRules,
            'imageBingoRules' => $this->getImageBingoRules($user),
            'bingoRules' => $this->getBingoRules($user),
        ];

        $viewData = ['route' => 'modules.game.bingo.configuration.index', 'data' => $viewData];

        return $this->handleViewData($viewData, $userType);
    }

    public function handleBingoRules(Request $request, $id = '')
    {
        if (!$request->rules) {
            return redirect()->back()->with([
                'type' => 'info',
                'message' => 'La información es obligatoria'
            ]);
        }

        if ($id) {
            $rulesID = decrypt($id);
        }

        DB::beginTransaction();

        try {

            $fileImage = $request->file('image');

            if (!empty($rulesID)) {

                $game = GameRules::findOrFail($rulesID);

                $updateData = [];

                if ($fileImage) {
                    $folder = 'game/bingo/bingo-rules';
                    $image = $fileImage ? $this->saveImage($fileImage, $folder, $game->encrypted) : null;

                    $updateData['image'] = $image;
                }

                if ($game->rules !== $request->rules) {
                    $updateData['rules'] = $request->rules;
                }

                if (!empty($updateData)) {
                    $game->update($updateData);
                }
            } else {

                $gameID = Games::getValueByName('BINGO');

                $code = $this->generateUniqueCode('GameRules', 12);

                if ($fileImage) {
                    $folder = 'game/bingo/bingo-rules';
                    $image = $fileImage ? $this->saveImage($fileImage, $folder, $code) : null;
                }

                GameRules::create([
                    'user' => Auth::id(),
                    'game' => $gameID,
                    'image' => $image,
                    'rules' => $request->rules,
                    'encrypted' => $code,
                    'status' => 1
                ]);
            }

            DB::commit();
            return redirect()->back()->with([
                'type' => 'success',
                'message' => 'Proceso existoso'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear o actualizar el reglamento del Juego de Bingo: ' . $e->getMessage());
            return redirect()->back()->with([
                'type' => 'warning',
                'message' => 'Error al crear o actualizar el reglamento'
            ]);
        }
    }

    public function handleBingoPlays(Request $request, $id = '', $action = '')
    {
        if ($id) {
            $playID = decrypt($id);
        }

        if ($action) {

            DB::beginTransaction();

            try {

                if ($action === 'status') {
                    $status = BingoTypesPlays::getValueById($playID, 'status');

                    // Verificar el valor actual de 'status' y actualizarlo
                    $newStatus = ($status == 0) ? 1 : 0; // Si es 1, cambia a 2, si es 2, cambia a 1

                    BingoTypesPlays::where('id', $playID)->update(['status' => $newStatus]);

                    DB::commit();

                    return redirect()->back()->with([
                        'type' => 'success',
                        'message' => 'Registro actualizado'
                    ]);
                } else if ($action === 'delete') {
                    BingoTypesPlays::where('id', $playID)->update(['status' => 2]);

                    DB::commit();

                    return redirect()->back()->with([
                        'type' => 'success',
                        'message' => 'Registro eliminado'
                    ]);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al crear o actualizar el tipo de jugada de Bingo: ' . $e->getMessage());
            }
        }

        // Definir variables
        $user = Auth::id();
        $namePlay = $request->name_play;
        $bingoBoxes = $request->bingo_boxes;

        // Verificar si el nombre de la jugada existe
        $existsName = BingoTypesPlays::existsBingoPlay('name', $namePlay, $user);
        if ($existsName) {
            return redirect()->back()->with([
                'type' => 'warning',
                'message' => 'El nombre de jugada ya esta en uso'
            ]);
        }
        // Verificar si el conjunto de casillas ya esta en uso
        $existsBoxes = BingoTypesPlays::existsBingoPlay('boxes', $bingoBoxes, $user);
        if ($existsBoxes) {
            return redirect()->back()->with([
                'type' => 'warning',
                'message' => 'Conjunto de casillas ya estan en uso'
            ]);
        }

        DB::beginTransaction();

        try {

            if (!empty($playID)) {

                $bingoPlays = BingoTypesPlays::findOrFail($playID);

                $updateData = [];

                if ($bingoPlays->name != $namePlay) {
                    $updateData['name'] = $namePlay;
                }

                if ($bingoPlays->boxes != $bingoBoxes) {
                    $updateData['boxes'] = $bingoBoxes;
                }

                if (!empty($updateData)) {
                    $bingoPlays->update($updateData);
                }
            } else {
                BingoTypesPlays::create([
                    'name' => $namePlay,
                    'user' => $user,
                    'boxes' => $bingoBoxes,
                    'status' => 1
                ]);
            }

            DB::commit();
            return redirect()->back()->with([
                'type' => 'success',
                'message' => 'Proceso existoso'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear o actualizar el tipo de jugada de Bingo: ' . $e->getMessage());
            return redirect()->back()->with([
                'type' => 'warning',
                'message' => 'Error al crear o actualizar el registro'
            ]);
        }
    }

    public function getInfo($id, $starTime = '')
    {
        date_default_timezone_set(config('app.timezone'));

        if ($starTime) {
            $time = time();
            return response()->json($time);
        }

        $roomID = decrypt($id);

        $room = BingoRoom::select(
            'encrypted',
            'room_type',
            'jackpot',
            'players'
        )->where('id', $roomID)->firstOrFail();

        $config = BingoRoomConfig::select(
            'game_mode',
            'date',
            'time',
            'ctn_price',
            'room_commn',
            'jackpot_type',
            'fixed_jackpot_value',
            'time_chip',
            'pct_serials',
            'num_serials',
            'serials_commn',
            'pct_types_plays',
            'types_plays'
        )->where('room', $roomID)->firstOrFail();

        $bingoCreator = true;

        $creatorUserID = BingoRoom::getValueById($roomID, 'creator_user');
        $userType = User::getValueById($creatorUserID, 'user_type');

        $creatorType = UserType::getValueById($userType, 'prefix');

        if ($creatorType === 'commerce') {
            $bingoCreator = BingoCreator::existsByRoomCreatorUser($roomID, $creatorUserID);

            if ($room->room_type === 'PB') {
                $userData = $this->getUserData()['userId'];
                if ($creatorUserID != $userData) {
                    $bingoCreator = BingoCreator::getByRoomCreatorUser($roomID, $userData, ['creator_commn', 'ctn_price']);
                }
            }
        }

        return response()->json([
            'room' => $room,
            'config' => $config,
            'creator' => $bingoCreator
        ]);
    }

    public function getInfoPublicRoom()
    {
        $userData = $this->getUserData();

        $allowedPrefixes = ['admin', 'commerce', 'customer'];

        if (!in_array($userData['type'], $allowedPrefixes)) {
            return false;
        }

        return BingoRoom::with([
            'Creator' => function ($query) use ($userData) {
                $query->select('room', 'status')
                    ->where('creator_user', $userData['userId']);
            },
            'Config:room,date,time,ctn_price'
        ])->select('id', 'encrypted', 'room_commn', 'jackpot')
            ->where('creator_user', '<>', $userData['userId'])
            ->where('room_type', 'PB')->where('status', 2)->get();
    }

    public function participateInPublicRoom($roomID = '', $getOut = false)
    {
        $userData = $this->getUserData();

        $allowedPrefixes = ['admin', 'commerce', 'customer'];

        if (!in_array($userData['type'], $allowedPrefixes)) {
            return false;
        }

        DB::beginTransaction();

        try {

            $creatorUser = $userData['userId'];

            if ($getOut) {
                $roomID = decrypt($roomID);

                BingoCreator::where('room', $roomID)
                    ->where('creator_user', $creatorUser)
                    ->update(['status' => 0]);
            } else {
                $bingo = BingoRoom::with('Config:id,room,ctn_price,room_commn')
                    ->where('encrypted', $roomID)->first();

                $bingoCreator = BingoCreator::select('id', 'status')
                    ->where('room', $bingo->id)
                    ->where('creator_user', $creatorUser)->first();

                if ($bingoCreator) {
                    $bingoCreator->update(['status' => 1]);
                } else {
                    BingoCreator::create([
                        'room' => $bingo->id,
                        'creator_user' => $creatorUser,
                        'creator_type' => UserType::getValueByPrefix($userData['type']),
                        'creator_id' => $userData['id'],
                        'creator_commn' => $bingo->Config->room_commn,
                        'ctn_price' => $bingo->Config->ctn_price
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('message', 'Proceso exitoso')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al participar/salir en la sala como comercializadora: ' . $e->getMessage());
            return redirect()->back()->with(['type' => 'warning', 'message' => 'Ha ocurrido un error']);
        }
    }

    public function getInfoBingoPlay($id)
    {
        $id = decrypt($id);

        $bingoPlay = BingoTypesPlays::where('id', $id)->first();

        $boxes = explode(',', $bingoPlay->boxes);

        return response()->json(['name' => $bingoPlay->name, 'boxes' => $boxes]);
    }

    public function bingoPlays($userToken = false)
    {
        if ($userToken) {
            $userType = 'customer';
            $userToken = true;
            $user = Customer::getValueByToken($userToken, 'user');
        } else {
            $user = Auth::id();
            $userTypeID = User::getValueById($user, 'user_type');
            $userType = UserType::getValueById($userTypeID, 'prefix');
        }

        if (!in_array($userType, ['admin', 'commerce', 'customer'])) {
            return redirect()->back();
        }

        $bingoPlays = BingoTypesPlays::where('user', $user)->where('status', '<>', 2)->get();

        $viewData = [
            'layout' => !$userToken ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Bingo Configuración',
            'name' => 'bingo-plays',
            'userToken' => $userToken,
            'bingoPlays' => $bingoPlays
        ];

        $viewData = ['route' => 'modules.game.bingo.configuration.handle-plays', 'data' => $viewData];

        return $this->handleViewData($viewData, $userType);
    }

    public function getInfoByEncrypt($id, $creatorUser, $coin)
    {
        $room = BingoRoom::getByEncrypted($id, ['id', 'jackpot_prize']);

        if (!$room) return response()->json(['Error' => 'Sala no encontrada']);

        $roomConfig = BingoRoomConfig::select(
            'game_mode',
            'unix_date',
            'ctn_price',
            'coin',
            'room_commn',
            'jackpot_type',
            'fixed_jackpot_value',
            'time_chip',
            'pct_serials',
            'num_serials',
            'serials_commn'
        )->where('room', $room->id)->firstOrFail();

        if (in_array($roomConfig->game_mode, [2, 3])) {

            $coin = decrypt($coin);
            $exchangeRate = 1;

            $creatorUser = decrypt($creatorUser);
            $creatorType = User::getUserTypePrefix($creatorUser);

            if ($creatorType === 'customer') {
                $isLogin = false;
                $custCoin = CustomerExchangeRate::getById($coin, ['coin', 'exchange_rate']);
                $coin = $custCoin->coin;
                $exchangeRate = $custCoin->exchange_rate;
            } else {
                $isLogin = ($coin == 1) ? $this->wallet()->usdt : $this->wallet()->balance;

                if ($coin != 1) {
                    $commerce = Commerce::getValueByUserId($creatorUser, 'commerce');
                    $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($commerce, $coin);
                }

                $coin = Coin::getValue($coin, 'symbol');
            }

            $ctnPrice = $roomConfig->ctn_price * $exchangeRate;

            $bingoCtnPrice = BingoCtnPrice::getValueCtnPrice($room->id, $coin, $creatorUser);

            if ($bingoCtnPrice) {
                $ctnPrice = $bingoCtnPrice;
            }

            $room = [
                'dateTime' => $roomConfig->unix_date,
                'coin' => $coin,
                'jackpotAmount' => number_format(($room->jackpot_prize * $exchangeRate), 2, '.'),
                'jackpotType' => $roomConfig->jackpot_type == 1 ? 'Bote' : 'Premio',
                'ctnPrice' => number_format(($ctnPrice), 2, '.')
            ];

            return response()->json(['room' => $room, 'isLogin' => $isLogin]);
        }

        return response()->json(['room' => $room, 'config' => $roomConfig]);
    }

    public function copy($id)
    {
        $roomID = decrypt($id);

        $existingRoom = BingoRoom::findOrFail($roomID);
        $existingRoomConfig = BingoRoomConfig::where('room', $roomID)->firstOrFail();
        $existingRoomCreator = BingoCreator::getByRoom($roomID);

        date_default_timezone_set(config('app.timezone'));

        $userID = $existingRoom->creator_user;
        $boat = $existingRoomConfig->jackpot_type > 1 ? $existingRoomConfig->fixed_jackpot_value : 0;
        $coin = $existingRoomConfig->coin ?? 1; // ID Coin == 1 (USDT)

        $model = 'BingoRoom';

        $createData = [
            'encrypted' => $this->generateUniqueCode($model),
            'status' => 1,
            'room_type' => $existingRoom->room_type,
            'creator_user' => $userID,
            'jackpot' => $boat,
            'code_uuid' => $this->getCodeUUID($model)
        ];

        // Definir $creator
        $creator = [
            'type' => $existingRoomCreator->creator_type,
            'id' => $existingRoomCreator->creator_id
        ];

        $bingoCreatorUser = BingoCreator::getValueByCreatorRoom($creator, $roomID, 'creator_user');
        $existingRoomCtnPrice = BingoCtnPrice::getAllCtnPrice($roomID, $bingoCreatorUser);

        DB::beginTransaction();

        try {
            // Obtener la nueva sala
            $newRoomID = BingoRoom::create($createData)->id;

            BingoRoomConfig::create([
                'room' => $newRoomID,
                'creation' => 'A',
                'creator_user' => $userID,
                'game_mode' => $existingRoomConfig->game_mode,
                'date' => $existingRoomConfig->date,
                'time' => $existingRoomConfig->time,
                'unix_date' => strtotime("{$existingRoomConfig->date} {$existingRoomConfig->time}"),
                'coin' => $coin,
                'ctn_price' => $existingRoomConfig->ctn_price,
                'room_commn' => $existingRoomConfig->room_commn,
                'jackpot_type' => $existingRoomConfig->jackpot_type,
                'fixed_jackpot_value' => $existingRoomConfig->fixed_jackpot_value,
                'time_chip' => $existingRoomConfig->time_chip,
                'pct_serials' => $existingRoomConfig->pct_serials,
                'num_serials' => $existingRoomConfig->num_serials,
                'serials_commn' => $existingRoomConfig->serials_commn,
                // 'pct_types_plays' => '',
                // 'types_plays' => ''
            ]);

            // Registar el creador de la sala
            BingoCreator::create([
                'room' => $newRoomID,
                'creator_user' => $userID,
                'creator_type' => $existingRoomCreator->creator_type,
                'creator_id' => $existingRoomCreator->creator_id,
                'creator_commn' => $existingRoomConfig->room_commn,
                'ctn_price' => $existingRoomConfig->ctn_price
            ]);

            if ($existingRoomCtnPrice) {

                $timestamp = now();

                $recordsToInsert = collect($existingRoomCtnPrice)
                    ->filter(fn($ctnPrice) => $ctnPrice) // Filtrar solo los elementos con valor
                    ->map(function ($ctnPrice, $coin) use ($newRoomID, $userID, $timestamp) {
                        return [
                            'room' => $newRoomID,
                            'creator_user' => $userID,
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
        $room = BingoRoom::with('Config')->findOrFail("id", decrypt($id));
        $response = $this->handleCreateAutomaticRoom($room, true);
        return redirect()->back()->with(['type' => $response['type'], 'message' => $response['message']]);
    }

    public function publish($id)
    {
        // Desencripta el ID y busca el registro correspondiente.
        $bingoRoom = BingoRoom::findOrFail(decrypt($id));

        date_default_timezone_set(config('app.timezone'));

        $unixDate = strtotime("{$bingoRoom->date} {$bingoRoom->time}");

        if ($unixDate < time()) {
            return redirect()->back()->with('message', 'Fecha/Hora no perimitidas')->with('type', 'warning');
        }

        DB::beginTransaction();

        try {
            // Actualiza el estado y la fecha de publicación.
            $bingoRoom->update(['status' => 2, 'publication_date' => Carbon::now()]);

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

                $commerce = Commerce::getById($creatorID, ['user', 'coin', 'code_uuid']);

                $GMRoute =  route('bingo.game-rooms-commerce', ['roomType' => 'private', 'coin' => encrypt($commerce->coin)]);

                if ($creatorID == 1) $roomType = Room::getRoomTypes();

                break;
            case 'customer': // Cliente

                $isLogin = $returnBtn = false;

                $customer = Customer::getById($creatorID, ['user', 'customer_id', 'token']);
                $token = $customer->token;

                $GMRoute =  route(
                    'bingo.game-rooms-customer',
                    ['customer' => $customer->customer_id, 'user' => 'available-rooms']
                );

                $moreViewData = ['customer' => true, 'userToken' => $token];

                break;

            default:
                return false;
                break;
        }

        $automaticRooms = BingoRoomAutomatic::where('creator_user', $creator['userId'])
            ->whereNot('status', 3)
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
                'pct_serials',
                'num_serials',
                'status'
            ]);

        $activeRooms = BingoRoom::where('creator_user', $creator['userId'])->whereIn('status', [2, 3])->exists();

        $timeZoneID = User::getValueById($creator['userId'], 'time_zone');
        $timeZone = TimeZone::getValueById($timeZoneID);

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Salas de Bingo',
            'name' => 'bingo-automatic-room',
            'returnBtn' => $returnBtn ?? true,
            'creator' => $creator,
            'timeZone' => $timeZone,
            'rooms' => $automaticRooms,
            'activeRooms' => $activeRooms,
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
                $creator = Commerce::getById($creatorID, ['id', 'user']);
                // Verificar si el comercio existe
                if (!$creator) {
                    return response()->json(['message' => 'El Comercio no existe'], 404);
                }
                break;

            case 'customer':
                $creator = Customer::getByToken($creatorID, ['id', 'user']);
                // Verificar si el cliente existe
                if (!$creator) {
                    $creator = Customer::getByUserId(Auth::id(), ['id', 'user']);
                    if (!$creator) {
                        return response()->json(['message' => 'Token del cliente no existe'], 404);
                    }
                }
                break;

            default:
                # code...
                break;
        }

        $creator = [
            'type' => $creatorType,
            'id' => encrypt($creator->id),
            'userId' => $creator->user
        ];

        // Obtener la información de configuracion de salas automaticas
        $viewData = $this->getAutomaticRoomsInfo($creator);

        // Retornar la vista con la información
        return $this->handleViewData($viewData, $creator['type']);
    }

    private function getOtherPublicRoomsInfo($coin)
    {
        (new HandleBingoRooms())->checkRoomClosure();

        $userData = $this->getUserData();

        switch ($userData['type']) {
            case 'admin': // Admin
                break;
            case 'commerce': // Comercio
                $isLogin = true;

                $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($userData['id'], $coin);

                $moreViewData = [
                    'uuidCode' => UUIDCodes::getValueByUser($userData['userId'], 'code_uuid')
                ];
                break;
            case 'customer':
                return false;
                break;

            default:
                return false;
                break;
        }

        $coin = Coin::getById($coin, ['id', 'symbol']);

        $coins = CommerceExchangeRate::getCoinsByCommerce($userData['id']);
        $coins[] = 1;
        $coins = Coin::getCoinsById($coins);

        $rooms = $this->getInfoRooms($userData['userId'], 'public-rooms', $coin, true);

        $creator = ['type' => $userData['type'], 'id' => encrypt($userData['id'])];

        $routeWithCoin = [
            'route' => 'bingo.all-rooms',
            'parameters' => [
                'roomType' => 'other-public-rooms',
                'creatorType' => $userData['type'],
                'creatorID' => $userData['id']
            ]
        ];

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Salas de Bingo',
            'subtitle' => 'Otras Salas Publicas',
            'name' => 'bingo',
            'returnBtn' => $returnBtn ?? true,
            'creator' => $creator,
            'coin' => $coin->id,
            'coinSym' => $coin->symbol,
            'coins' => $coins,
            'exchangeRate' => $exchangeRate ?? 1,
            'rooms' => $rooms,
            'routeWithCoin' => $routeWithCoin
        ];

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.game.bingo.form.other-public-rooms', 'data' => $viewData];
    }

    private function allOtherPublicRooms($creatorType, $creatorID, $coin = '')
    {
        if (!$coin) {
            return redirect()->route('bingo.all-rooms', [
                'roomType' => 'other-public-rooms',
                'creatorType' => $creatorType,
                'creatorID' => $creatorID,
                'coin' => encrypt(1)
            ]);
        }

        $creatorID = decrypt($creatorID);

        switch ($creatorType) {
            case 'commerce':
                $creator = Commerce::getById($creatorID, ['id', 'user']);
                // Verificar si el comercio existe
                if (!$creator) {
                    return response()->json(['message' => 'El Comercio no existe'], 404);
                }
                break;

            case 'customer':
                $creator = Customer::getByToken($creatorID, ['id', 'user']);
                // Verificar si el cliente existe
                if (!$creator) {
                    $creator = Customer::getByUserId(Auth::id(), ['id', 'user']);
                    if (!$creator) {
                        return response()->json(['message' => 'Token del cliente no existe'], 404);
                    }
                }
                break;

            default:
                # code...
                break;
        }

        // Obtener la información de otras salas publicas
        $viewData = $this->getOtherPublicRoomsInfo(decrypt($coin));

        // Retornar la vista con la información
        return $this->handleViewData($viewData, $creatorType);
    }

    private function handleCreateAutomaticRoom($data, $replay = false)
    {
        DB::beginTransaction();

        try {
            $creator = json_decode($data->creator);

            $automaticBingoID = BingoRoomAutomatic::create([
                'time' => $data->time,
                'game_mode' => $data->game_mode,
                'room_type' => $data->room_type,
                'jackpot_type' => $data->jackpot_type,
                'fixed_jackpot_value' => $data->fixed_jackpot_value,
                'time_chip' => $data->time_chip,
                'ctn_price' => $data->ctn_price,
                'room_commn' => $data->room_commn,
                'serials_commn' => $data->serials_commission,
                'pct_serials' => $data->pct_serials,
                'num_serials' => $data->num_serials,
                'creator_type' => UserType::getValueByPrefix($creator->type, 'id'),
                'creator_id' => decrypt($creator->id),
                'creator_user' => Auth::id()
            ])->id;

            if (!empty($data->ctn_price_coins)) {
                $timestamp = now();

                $recordsToInsert = array_map(fn($coin, $ctnPrice) =>
                $ctnPrice ? [
                    'automatic_bingo_id' => $automaticBingoID,
                    'coin' => $coin,
                    'ctn_price' => $ctnPrice,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ] : null, array_keys((array) $data->ctn_price_coins), (array) $data->ctn_price_coins);

                BingoCtnPriceAutomatic::insert(array_filter($recordsToInsert));
            }

            DB::commit();
            return ['type' => 'success', 'message' => 'Registro creado con éxito'];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear sala automática: ' . $e->getMessage());
            return ['type' => 'warning', 'message' => 'Hubo un error al crear la sala'];
        }
    }

    public function createAutomaticRoom(Request $request)
    {
        $response = $this->handleCreateAutomaticRoom($request);
        return redirect()->back()->with(['type' => $response['type'], 'message' => $response['message']]);
    }

    public function updateAutomaticRoom(Request $request, $id)
    {
        $id = decrypt($id);

        DB::beginTransaction();

        try {
            BingoRoomAutomatic::where('id', $id)->update([
                'time' => $request->time,
                'game_mode' => $request->game_mode,
                'room_type' => $request->room_type,
                'jackpot_type' => $request->jackpot_type,
                'fixed_jackpot_value' => $request->fixed_jackpot_value,
                'time_chip' => $request->time_chip,
                'ctn_price' => $request->ctn_price,
                'room_commn' => $request->room_commn,
                'pct_serials' => $request->pct_serials,
                'num_serials' => $request->num_serials
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
            'room_commn',
            'ctn_price',
            'time_chip',
            'pct_serials',
            'num_serials',
            'serials_commn',
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
        $symbols = BingoDrawCode::select('sym', 'num')->where('room', $room)->pluck('sym', 'num');
        $draws = BingoDraw::where('room', $room)->get();
        $currentTimestamp = now()->addMinutes(1)->timestamp;
        $timeIncrement = 8;

        // Eliminar Ultima Ficha en salir (ganadora)
        BingoDraw::where('room', $room)->where('status', 3)->update(['status' => 0]);

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

            // Reiniciar por defecto registros de ganadores y usuarios ganadores
            BingoRoomUser::where('room', $room)->where('winner', 1)->update(['winner' => 0]);
            // Eliminar registros de cartones premiados (ganadores)
            BingoPrizeCtnsPayment::where('room', $room)->delete();
            BingoPrizeCtns::where('room', $room)->delete();

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

            // Ajustar status de la sala y reiniciar cierre de sala
            BingoRoom::where('id', $room)->update([
                'status' => 3,
                'paid_win' => 0,
                'jackpot_prize' => BingoRoom::getValueById($room, 'jackpot'),
                'serial_prize' => 0,
                'close_room' => null
            ]);

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
                'message' => 'Ha ocurrido un error'
            ]);
        }
    }

    public function notifyPaymentPrizeWinningCtn(Request $request)
    {
        try {
            $ctnId = decrypt($request->ctn);
        } catch (DecryptException $e) {
            return response()->json(['error' => 'No se pudo descifrar el ID del cartón.'], 400);
        }

        $validator = Validator::make(['ctnId' => $ctnId], [
            'ctnId' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $ctnLot = BingoCtns::getValueById($ctnId, 'lot');
        if (!$ctnLot) return redirect()->back();

        $prizeCtn = BingoPrizeCtns::where('ctn', $ctnId)->first();
        if (!$prizeCtn) return response()->json(['error' => 'Cartón no encontrado.'], 404);

        // Definir tipo de pago/operacion
        $paymentType = $request->payment_type;

        $existingPayment = BingoPrizeCtnsPayment::where('prize_ctn', $prizeCtn->id)
            ->where('payment', $paymentType)->exists();

        if ($existingPayment) return redirect()->back();

        DB::beginTransaction();

        try {
            // Guardar Imagen de comprobante de pago
            $filePayment = FileController::saveImage($request->file('payment_proof'), 'game/bingo/payments/prize-ctns', 'PV');

            // Registar informacion sobre pago de carton premiado
            $prizeCartonPayment = $this->registerPrizeCartonPayment($prizeCtn, $paymentType, Auth::id(), $filePayment);

            if (!$prizeCartonPayment) {
                DB::rollBack();
            }

            // Actualizar estado del cartón según la operación
            BingoPrizeCtns::where('ctn', $ctnId)->update(['status' => $paymentType]);

            if ($paymentType === 'SP') {
                $success = BingoRoomUser::where('player_link', $prizeCtn->player_link)->update(['winner' => 2]);
                if (!$success) {
                    DB::rollBack();
                    return response()->json(['error' => 'Error al actualizar el estado del cartón.'], 500);
                }
                $this->allWinCtnPaid($prizeCtn->room);
            }

            DB::commit();
            return redirect()->back()->with('message', 'Pago notificado con éxito');
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($filePayment)) {
                Storage::delete('private/game/bingo/payments/prize-ctns/' . $filePayment);
            }
            Log::error('Error al procesar el pago: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Hubo un error al procesar el pago.'], 500);
        }
    }

    // Seccion de Reporte de Salas
    private function getRoomsData($user, $coin, $dateFilter = "today")
    {
        switch ($user['type']) {

            case 'commerce':

                $creatorUser = $user['userId'];

                $query = BingoRoom::with([
                    'Config:room,date,time,ctn_price,room_commn',
                    'Status',
                    'CtnsLot' => function ($subquery) use ($creatorUser, $coin) {
                        $subquery->where('creator_user', $creatorUser)
                            ->where('coin', $coin)->with('Coin');
                    }
                ])->whereHas('CtnsLot', function ($query) use ($creatorUser, $coin) {
                    $query->where('creator_user', $creatorUser)->where('coin', $coin);
                });

                break;
            case 'seller':

                $seller = $this->seller(['id']);
                $sellerId = $seller->id;

                $query = BingoRoom::with([
                    'Config:room,time,date,ctn_price',
                    'CtnsLot' => function ($query) use ($sellerId, $coin) {
                        $query->where('seller', $sellerId)->where('coin', $coin)->with('Coin');
                    }
                ])->whereHas('CtnsLot', function ($query) use ($sellerId, $coin) {
                    $query->where('seller', $sellerId)->where('coin', $coin);
                });

                break;
            default:
                # code...
                break;
        }

        $query = $this->applyDateFilter($dateFilter, $query, 'publication_date');

        $rooms = $query->get(['id', 'encrypted', 'room_type', 'status', 'jackpot']);

        return $rooms;
    }

    public function reportSalesRooms($coin = '', $dateFilter = "today")
    {
        $userData = $this->getUserData();

        $verifyCoin = $this->verifyExistenceOfSales($userData, $dateFilter, $coin);

        if (isset($verifyCoin['return'])) {
            return redirect()->route(
                'bingo.reportSalesRooms',
                ['coin' => encrypt($verifyCoin['coin']), 'dateFilter' => 'all']
            );
        }

        $coin = $verifyCoin;

        $dataCoin = Coin::getById($coin, ['symbol', 'short_name', 'iso_code']);
        $coinSym = $dataCoin->symbol;
        $coinName = "$dataCoin->short_name ($dataCoin->iso_code)";

        $routeWithCoin = [
            'route' => 'bingo.reportSalesRooms',
            'parameters' => ['coin' => encrypt($coin), 'dateFilter' => $dateFilter]
        ];

        // Obtener los datos de los ganadores
        $rooms = $this->getRoomsData($userData, $coin, $dateFilter);

        $title = 'Reporte de Ventas';

        switch ($userData['type']) {

            case 'commerce':

                $coins = $this->getUserCoins($userData, ['CtnsLot'], $dateFilter);

                $totals = [
                    'allCtns' => 0,
                    'allSales' => 0,
                    'comSaleSeller' => 0,
                    'finalSale' => 0,
                    'jackpot' => 0,
                    'comBanca' => 0,
                ];

                $roomData = [];

                foreach ($rooms as $room) {

                    $comRKE = 0;
                    // Operaciones numéricas reales
                    $allSaleRoom = $room->CtnsLot->sum('sale_amount');
                    $finalSaleRoom = $room->CtnsLot->sum('final_sale_amount');
                    $comSaleSellerRoom = $allSaleRoom - $finalSaleRoom;
                    $comBancaRoom = $allSaleRoom * ($room->Config->room_commn / 100);
                    $jackpotRoom = $allSaleRoom - $comSaleSellerRoom - $comRKE - $comBancaRoom;

                    $ctns = $room->CtnsLot->sum('ctns');

                    // Sumar a totales
                    $totals['allCtns'] += $ctns;
                    $totals['allSales'] += $allSaleRoom;
                    $totals['comSaleSeller'] += $comSaleSellerRoom;
                    $totals['finalSale'] += $finalSaleRoom;
                    $totals['jackpot'] += $jackpotRoom;
                    $totals['comBanca'] += $comBancaRoom;

                    // Formatear para mostrar por sala
                    $roomData[] = [
                        'encrypted' => $room->encrypted,
                        'roomType' => $room->room_type_description,
                        'date' => $room->Config->date,
                        'time' => $room->Config->time,
                        'allSaleRoom' => number_format($allSaleRoom, 2, '.', ','),       // separador de miles
                        'finalSaleRoom' => number_format($finalSaleRoom, 2, '.', ','),
                        'comSaleSellerRoom' => number_format($comSaleSellerRoom, 2, '.', ','),
                        'comBancaRoom' => number_format($comBancaRoom, 2, '.', ','),
                        'jackpotRoom' => number_format($jackpotRoom, 2, '.', ','),
                        'allCtns' => $ctns
                    ];
                }

                // Formatear los totales
                $totalsFormatted = [
                    'allCtns' => $totals['allCtns'],
                    'allSales' => number_format($totals['allSales'], 2, '.', ','),
                    'comSaleSeller' => number_format($totals['comSaleSeller'], 2, '.', ','),
                    'finalSale' => number_format($totals['finalSale'], 2, '.', ','),
                    'jackpot' => number_format($totals['jackpot'], 2, '.', ','),
                    'comBanca' => number_format($totals['comBanca'], 2, '.', ','),
                ];

                return view(
                    'modules.commerce.games.bingo.report.rooms',
                    compact('title', 'rooms', 'routeWithCoin', 'dateFilter', 'coin', 'coins', 'coinSym', 'coinName', 'roomData', 'totalsFormatted')
                );
                break;

            case 'seller':

                $coins = $this->getUserCoins($userData, ['CtnsLot'], $dateFilter);

                // Inicializar los totales
                $totalSale = 0;
                $totalComision = 0;
                $totalBanca = 0;

                // Almacenar cálculos en una colección
                $roomsData = [];

                // Realizar los cálculos
                foreach ($rooms as $room) {
                    $roomCtns = $room->CtnsLot->sum('ctns');
                    $roomSale = $room->CtnsLot->sum('sale_amount');
                    $roomSaleCom = $roomSale - $room->CtnsLot->sum('final_sale_amount');

                    $totalSale += $roomSale;
                    $totalComision += $roomSaleCom;

                    // Guardar los cálculos en la colección
                    $roomsData[] = [
                        'room' => $room,
                        'calculatedSale' => $roomSale,
                        'calculatedCtns' => $roomCtns,
                        'calculatedCom' => $roomSaleCom,
                    ];
                }

                return view(
                    'modules.seller.games.bingo.report.rooms',
                    compact('title', 'rooms', 'roomsData', 'routeWithCoin', 'dateFilter', 'coins', 'coin', 'coinName', 'coinSym', 'totalSale', 'totalComision')
                );

                break;

            default:
                # code...
                break;
        }
    }

    public function reportSalesByRoom($roomID, $coin)
    {
        $userData = $this->getUserData();

        $room = BingoRoom::getByEncrypted($roomID, ['id', 'encrypted', 'creator_user']);
        $roomID = $room->id;
        $bingoConfig = BingoRoomConfig::getByRoom($roomID, ['ctn_price', 'room_commn']);

        $routeWithCoin = [
            'route' => 'bingo.reportSalesByRoom',
            'parameters' => ['room' => $roomID, 'coin' => $coin]
        ];

        $coinID = decrypt($coin);

        $existSale = $this->verifyExistenceOfSales($userData, '', $coinID, $roomID);

        if (!$existSale) {
            // Si no existe venta para esa fecha
            return redirect()->route(
                'bingo.reportSalesByRoom',
                ['room' => $roomID, 'coin' => $coin]
            );
        }

        $title = 'Reporte de Sala';
        $cardTitle = "<span> Reporte de Sala </span> <span class='badge badge-secondary'>{$room->encrypted}</span>";

        $coin = Coin::getById($coinID, ['id', 'symbol']);

        $where = collect([
            (object)[
                'relation' => 'CtnsLot',
                'constraints' => function ($query) use ($roomID) {
                    $query->where('room', $roomID);
                }
            ]
        ]);

        switch ($userData['type']) {

            case 'commerce':

                $ctnsLotFilter = fn($query) => $query->where('room', $roomID)->where('coin', $coin->id);

                $sellers = Seller::select('id', 'name', 'country')
                    ->with([
                        'Country:id,name',
                        'CtnsLot' => $ctnsLotFilter,
                    ])
                    ->where('commerce', $userData['id'])
                    ->whereHas('CtnsLot', $ctnsLotFilter)
                    ->get();

                $coins = $this->getUserCoins($userData, ['CtnsLot'], 'all', $where);

                return view('modules/commerce/games/bingo/report/by-room')->with([
                    'title' => $title,
                    'cardTitle' => $cardTitle,
                    'sellers' => $sellers,
                    'comCommerceRoom' => $bingoConfig->room_commn / 100,
                    'room' => $room->encrypted,
                    'routeWithCoin' => $routeWithCoin,
                    'coin' => $coin->id,
                    'coinSym' => $coin->symbol,
                    'coins' => $coins
                ]);
                break;

            case 'seller':

                $seller = Seller::getById($userData['id'], ['id', 'commerce']);

                $coin = $coin->id;
                $coinSym = Coin::getValue($coin, 'symbol');

                $playersRoom = PlayerLink::with(['Coin', 'CtnsLot'])
                    ->where(['seller' => $seller->id, 'room' => $roomID, 'coin' => $coin])
                    ->orderByDesc('id')->get(['id', 'link', 'coin', 'name', 'lastname', 'phone']);

                if (!count($playersRoom)) return redirect()->back();

                // Realizar los cálculos de CTNs 
                $totalCtnsPurchased = $playersRoom->pluck('CtnsLot')->flatten()->sum('sale_amount');

                $totalCtnsByRoom = $playersRoom->flatMap->CtnsLot->sum('ctns');

                $coins = $this->getUserCoins($userData, ['CtnsLot'], 'all', $where);

                $commerce = Commerce::getValueById($seller->commerce, 'code_uuid');

                return view(
                    'modules.seller.games.bingo.report.by-room',
                    compact(
                        'title',
                        'cardTitle',
                        'commerce',
                        'playersRoom',
                        'routeWithCoin',
                        'coins',
                        'coin',
                        'coinSym',
                        'totalCtnsPurchased',
                        'totalCtnsByRoom'
                    )
                );
                break;
            default:
                # code...
                break;
        }
    }

    public function ctnsSold($coin = '', $dateFilter = 'today')
    {
        $userData = $this->getUserData();

        $verifyCoin = $this->verifyExistenceOfSales($userData, $dateFilter, $coin);

        if (isset($verifyCoin['return'])) {
            return redirect()->route(
                'bingo.ctnsSold',
                ['coin' => encrypt($verifyCoin['coin']), 'dateFilter' => 'all']
            );
        }

        $title = 'Cartones Vendidos';

        $routeWithCoin = [
            'route' => 'bingo.ctnsSold',
            'parameters' => ['coin' => $coin, 'dateFilter' => $dateFilter]
        ];

        switch ($userData['type']) {

            case 'commerce':

                $coin = $verifyCoin;

                $coinSym = Coin::getValue($coin, 'symbol');

                $ctnsSoldQuery = BingoCtnsLot::with(['Room', 'Coin', 'Payment', 'Seller'])
                    ->whereHas('Room', function ($query) use ($userData) {
                        $query->where('creator_user', $userData['userId'])->whereIn('status', [2, 3, 4]);
                    });

                $ctnsSoldQuery = $this->applyDateFilter($dateFilter, $ctnsSoldQuery);

                $ctnsSold = $ctnsSoldQuery->where('coin', $coin)->where('creator_user', $userData['userId'])
                    ->whereHas('Seller')->orderBy('status')->orderByDesc('id')->get();

                $coins = $this->getUserCoins($userData, ['CtnsLot', 'Coin'], $dateFilter);

                return view(
                    'modules.commerce.games.bingo.cartons.ctns-sold',
                    compact('title', 'ctnsSold', 'routeWithCoin', 'dateFilter', 'coin', 'coinSym', 'coins')
                );
                break;

            case 'seller':

                $seller = Seller::getById($userData['id'], ['id', 'commerce']);

                $coin = Coin::getById($verifyCoin);

                $ctnsSoldQuery = BingoCtnsLot::with([
                    'Payment',
                    'PlayerLink',
                    'ComSellerRoom' => function ($query) use ($seller) {
                        $query->where('seller', $seller->id);
                    }
                ]);

                $ctnsSoldQuery = $this->applyDateFilter($dateFilter, $ctnsSoldQuery);

                $ctnsSold = $ctnsSoldQuery->where('coin', $coin->id)
                    ->where('seller', $seller->id)->orderBy('status')->orderByDesc('id')->get();

                $allPendingCtnSale = $pendingCtns = 0;

                foreach ($ctnsSold as $value) {
                    if ($value->status == 0) {
                        $allPendingCtnSale += $value->sale_amount;
                        $pendingCtns++;
                    }
                }

                $coins = $this->getUserCoins($userData, ['CtnsLot'], $dateFilter);

                return view('modules/seller/games/bingo/cartons/ctns-sold')->with([
                    'title' => 'Cartones Vendidos',
                    'name' => 'notify-payment-ctns-off',
                    'seller' => encrypt($seller->id),
                    'allPendingCtnSale' => $allPendingCtnSale,
                    'pendingCtns' => $pendingCtns,
                    'ctnsSold' => $ctnsSold,
                    'routeWithCoin' => $routeWithCoin,
                    'coin' => $coin->id,
                    'coinName' => $coin->short_name,
                    'coinISOCode' => $coin->iso_code,
                    'coinSym' => $coin->symbol,
                    'dateFilter' => $dateFilter,
                    'commerceName' => Commerce::getById($seller->commerce)->name,
                    'coins' => $coins
                ]);

                break;

            default:
                # code...
                break;
        }
    }

    public function ctnsAwarded($type = 'private', $dateFilter = "today", $coin = '')
    {
        if (!in_array($type, ['private', 'public'])) {
            abort(404); // Retorna un error 404 Not Found
        }

        if ($type === 'public') return redirect()->back();

        $userData = $this->getUserData();

        $verifyCoin = $this->verifyExistenceOfSales($userData, $dateFilter, $coin);

        if (isset($verifyCoin['return'])) {
            return redirect()->route(
                'bingo.ctnsAwarded',
                [
                    'type' => $type,
                    'dateFilter' => 'all',
                    'coin' => encrypt($verifyCoin['coin'])
                ]
            );
        }

        $routeWithCoin = [
            'route' => 'bingo.ctnsAwarded',
            'parameters' => [
                'type' => $type,
                'dateFilter' => $dateFilter,
                'coin' => $coin
            ]
        ];

        $coin = $verifyCoin;

        app(HandleBingoRooms::class)->checkRoomClosure();

        $title = 'Ganadores';
        $name = 'notify-payment-win-ctns-off';

        switch ($userData['type']) {

            case 'commerce':

                $creatorUser = $userData['userId'];

                // Pagos Pendientes de otros comercios
                $paymentsOtherCommerces = 0;

                // Contar los ganadores pendientes de pago para otros comercios
                $countWinnersOtherCommerces = BingoPrizeCtns::getPendingPaymentsCountExcludingCreator($creatorUser);

                // Contar los ganadores pendientes de pago para el propio comercio
                $countWinnersByCommerce = BingoPrizeCtns::getPendingPaymentsCountByCreator($creatorUser);

                $coin = $this->wallet()->Coin->id;

                // Obtener las monedas asociadas al comercio
                $where = collect([
                    (object)[
                        'relation' => 'PlayerLink',
                        'constraints' => function ($query) use ($creatorUser) {
                            $query->whereColumn('coin', 'commerces_countries_coins.coin')
                                ->where('creator_user', $creatorUser);
                        }
                    ],
                    (object)[
                        'relation' => 'PlayerLink.PlayerGame',
                        'constraints' => function ($query) {
                            $query->where('winner', '>', 0);
                        }
                    ]
                ]);

                $coins = $this->getUserCoins($userData, ['Coin', 'PlayerLink.PlayerGame'], $dateFilter, $where);

                $commerce = Commerce::getById($userData['id'], ['code_rke', 'code_uuid']);
                $commerce = $commerce->code_rke ?? $commerce->code_uuid;

                // Obtener los datos de los ganadores
                $winnersQuery = BingoPrizeCtns::with([
                    'Room:id,encrypted',
                    'Room.Config:id,room,date,time',
                    'Coin',
                    'PlayerLink.Country',
                    'Payments',
                    'Seller' => function ($query) {
                        $query->select('id', 'name');
                    },
                    'Prize' => function ($query) {
                        $query->select('id', 'description');
                    },
                    'GameExchangeRate' => function ($query) {
                        $query->where('game', 1);
                    }
                ])->whereHas('Room', function ($query) {
                    $query->where('status', '<', 5)->with('CtnsLot');
                })->whereHas('Seller')->whereHas('GameExchangeRate');

                if ($type === 'private') {
                    $winnersQuery->where('creator_user', $creatorUser);
                } else {
                    $winnersQuery->whereNot('creator_user', $creatorUser);
                }

                $winnersQuery = $this->applyDateFilter($dateFilter, $winnersQuery);

                $winners = $winnersQuery->where('active', 'Y')->orderBy('prize_type')->get();

                if (!count($winners) && $dateFilter !== 'all') {
                    return redirect()->route(
                        'bingo.ctnsAwarded',
                        ['type' => $type, 'dateFilter' => 'all']
                    );
                }

                if ($type === 'private') {

                    return view(
                        'modules.commerce.games.bingo.cartons.awarded.my-winners',
                        compact(
                            'title',
                            'name',
                            'dateFilter',
                            'commerce',
                            'winners',
                            'routeWithCoin',
                            'paymentsOtherCommerces',
                            'countWinnersOtherCommerces',
                            'countWinnersByCommerce',
                            'coin',
                            'coins'
                        )
                    );
                } else {
                    return view(
                        'modules.commerce.games.bingo.cartons.awarded.other-winners',
                        compact(
                            'title',
                            'name',
                            'commerceID',
                            'dateFilter',
                            'winners',
                            'countWinnersOtherCommerces',
                            'countWinnersByCommerce',
                            'coins'
                        )
                    );
                }

                break;

            case 'seller':

                $seller = Seller::getById($userData['id'], ['id', 'commerce']);

                $winnersQuery = BingoPrizeCtns::with([
                    'Coin',
                    'Prize',
                    'GameExchangeRate',
                    'Room',
                    'PlayerLink.Country',
                    'PlayerGame'
                ])->whereHas('Room', function ($query) {
                    $query->whereColumn('id', 'bingo_prize_ctns.room')->where('status', 4);
                })->whereHas('GameExchangeRate', function ($query) use ($coin) {
                    $query->where('game', 1)->where('coin', $coin);
                });

                $winnersQuery = $this->applyDateFilter($dateFilter, $winnersQuery);

                $winners = $winnersQuery->where('seller', $seller->id)
                    ->where('active', 'Y')->where('coin', $coin)->get();

                $commerce = Commerce::getById($seller->commerce, ['code_rke', 'code_uuid']);

                $commerce = $commerce->code_rke ?? $commerce->code_uuid;

                $coins = $this->getUserCoins($userData, ['PlayerLink', 'PrizeCtns'], $dateFilter);

                $coinInfo = Coin::getById($coin, ['id', 'symbol', 'iso_code']);
                $coinSym = $coinInfo->symbol;
                $coinISOCode = $coinInfo->iso_code;

                return view(
                    'modules.seller.games.bingo.cartons.awarded',
                    compact('title', 'name', 'commerce', 'winners', 'routeWithCoin', 'dateFilter', 'coin', 'coinSym', 'coinISOCode', 'coins')
                );

                break;

            default:
                # code...
                break;
        }
    }
}
