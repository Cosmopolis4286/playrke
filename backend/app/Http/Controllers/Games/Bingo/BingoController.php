<?php

namespace App\Http\Controllers\Games\Bingo;

use App\Http\Controllers\Controller;

// Events
use App\Events\Games\Bingo\BingoNumberCalled;

// Models
use App\Models\Bingo\Cards\CardPrice\BingoCardPrice;
use App\Models\Bingo\Cards\CardPrice\BingoCardPriceDraft;
use App\Models\BingoCtns;
use App\Models\BingoCtnsLot;
use App\Models\BingoDraw;
use App\Models\BingoDrawCode;
use App\Models\BingoPrizeCtns;
use App\Models\BingoPrizeCtnsPayment;
use App\Models\BingoRoom;
use App\Models\BingoRoomAutomatic;
use App\Models\BingoRoomConfig;
use App\Models\BingoRoomUser;
use App\Models\BingoRound;
use App\Models\BingoRoundDraft;
use App\Models\BingoCreator;
use App\Models\BingoCreatorSale;
use App\Models\BingoPatterns;
use App\Models\Currency;
use App\Models\Commerce;
use App\Models\CommerceCurrency;
use App\Models\Roles\Commerce\CommerceExchangeRate;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Roles\Customer\CustomerExchangeRate;
use App\Models\CustomerGame;
use App\Models\CustomerUser;
use App\Models\GameConfig;
use App\Models\GameModes;
use App\Models\GameRule;
use App\Models;
use App\Models\ExternalPlayers;
use App\Models\PlayHistory;
use App\Models\PrizePoolType;
use App\Models\Room;
use App\Models\Seller;
use App\Models\TimeZone;
use App\Models\Users;
use App\Models\UsersPurchaseCodes;
use App\Models\Role;
use App\Models\Shared\UniqueCode;
use App\Models\Wallet;
use App\Repositories\User\Roles\RoleRepository;
// Services
use App\Services\Games\Bingo\AutoGenerateBingoRoomsService;
use App\Services\Games\Bingo\HandleBingoRoomsService;
use App\Services\Games\Room\RoomTypeService;
use App\Services\Route\RouteService;
use App\Services\Users\Customer\HandleTransactions;

// Traits
use App\Traits\Games\BingoTrait;
use App\Traits\Games\GameTrait;
use App\Traits\Helpers\EncryptTrait;
use App\Traits\Helpers\FiltersTrait;
use App\Traits\Users\Roles\Customer\CustomerTrait;
use App\Traits\Users\UserTrait;

// Otros
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BingoController extends Controller
{
    use BingoTrait, CustomerTrait, EncryptTrait, FiltersTrait, GameTrait, UserTrait;

    protected RoomTypeService $roomTypeService;
    protected RouteService $routeService;

    public function __construct(RoomTypeService $roomTypeService, RouteService $routeService)
    {
        $this->roomTypeService = $roomTypeService;
        $this->routeService = $routeService;
    }

    public function getDateTimeRooms($creatorUser, $player = 'GAMES')
    {
        $player = $player === 'GAMES' ? null : decrypt($player);

        $rooms = BingoRoom::with([
            'PlayersRoom' => fn($q) => $player ?
                $q->where('id', $player)->select('id', 'room') :
                $q->select('id', 'room'),
            'Config:room,unix_date'
        ])->select('id', 'encrypted', 'status')
            ->where('creator_user_id', decrypt($creatorUser))
            ->whereIn('status', [2, 3])->get();

        return [
            'rooms' => $rooms,
            'timestamp' => now()->timestamp * 1000,
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

    private function getGamesRoomsView($creator, $data = [])
    {
        if (!in_array($data['roomType'], ['private', 'public'])) {
            return ['redirect' => 'redirect-back'];
        }

        // Verificar Cierre de Salas
        app(HandleBingoRoomsService::class)->checkRoomClosure();

        $creatorUser = $creator['userId'];

        // Obtener infomacion de Salas de Juego (CARDS)
        switch ($creator['type']) {
            case 'admin': // Admin
                break;
            case 'commerce': // Comercio
                $creatorId = $creator['id'];

                $isLogged = true;
                $currency = $data['currency'];
                $currencySym = Currency::getValue($currency, 'symbol');
                $exchangeRate = CommerceExchangeRate::getCommerceCurrencyValue($creatorId, $currency);

                $playerUser = $data['player'];

                break;
            case 'customer': // Cliente

                $creatorId = $creator['id'];

                $isLogged = false;
                $currency = $data['currency'];
                $custCurrency = CustomerExchangeRate::getById($currency, ['currency', 'exchange_rate']);
                $currencySym = $custCurrency->currency;
                $exchangeRate = $custCurrency->exchange_rate;

                $playerUser = $data['player'];
                $player = $$playerUser ? encrypt($playerUser) : 'GAMES';

                $moreViewData = [
                    'customerId' => Customer::getValueById($creatorId, 'customer_id'),
                    'userToken' => $data['userToken']
                ];
                break;

            default:
                # code...
                break;
        }

        // Definir roomType
        $roomType = $data['roomType'] === 'private' ? 'PV' : 'PB';

        $rooms = BingoRoom::with([
            'Config' => function ($query) {
                $query->select('room', 'game_mode', 'prize_pool_type', 'date', 'time', 'currency', 'card_price');
            },
            'CtnPrice' => function ($query) use ($creatorUser, $currency) {
                $query->where('creator_user_id', $creatorUser)
                    ->where('currency', $currency);
            },
            'PlayersRoom' => function ($query) use ($playerUser) {
                $query->select('id', 'room', 'ctns')
                    ->where('player_user', $playerUser);
            }
        ])
            ->select(
                'id',
                'status',
                'players',
                'prize_pool',
                'ctns',
                'encrypted'
            )
            ->where('creator_user_id', $creatorUser)
            ->where('room_type', $roomType)
            ->whereHas('Config', function ($query) {
                $query->whereIn('game_mode', [1, 3])
                    ->whereNotNull('room');
            })
            ->whereIn('status', [2, 3])
            ->orderByRaw("(SELECT unix_date FROM bingo_rooms_config WHERE bingo_rooms_config.room = bingo_rooms.id LIMIT 1)")
            ->get();

        // Redirigir si no hay salas disponibles
        if (!count($rooms) && $creator['type'] === 'commerce_id') {
            return ['redirect' => route(
                'bingo.room-summary',
                [
                    'roomType' => $roomType == 1 ? 'public-rooms' : 'private-rooms',
                    'creatorType' => 'commerce_id',
                    'creatorId' => encrypt($creatorId),
                    'currency' => encrypt($currency)
                ]
            )];
        }

        $creator['id'] = encrypt($creator['id']);

        $viewData = [
            'bladeLayout' => $isLogged ? 'layouts.auth-layout' : 'layouts.guest-layout',
            'title' => 'Bingo Salas de Juego',
            'currencySymbol' => $currencySym,
            'currency' => encrypt($currency),
            'exchangeRate' => $exchangeRate ?? 1,
            'creator' => $creator,
            'creatorUser' => encrypt($creatorUser),
            'player' => $player,
            'rooms' => $rooms,
            'imageBingoRules' => $this->getImageBingoRules($creatorUser),
            'bingoRules' => $this->getBingoRules($creatorUser),
            'dateTimeRooms' => $this->getDateTimeRooms(encrypt($creatorUser), $player)
        ];

        if ($currency == 1) $viewData['USDT'] = true;

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }
        // Retorna ruta y datos
        return ['route' => 'modules.game.bingo.game-rooms', 'data' => $viewData];
    }

    public function gamesRoomsCommerce($roomType = 'private', $currency = '')
    {
        // Vista para todas las salas disponibles o en juego de un comercio

        // Obtner ID d Comercio a traves del ID de usuario
        $commerce = Commerce::getValueByUserId(Auth::id(), 'id');

        if (!$currency) {
            return redirect()->route(
                'bingo.room-summary',
                [
                    'roomType' => $roomType === 'private' ? 'private-rooms' : 'public-rooms',
                    'creatorType' => 'commerce_id',
                    'creatorId' => encrypt($commerce),
                    'currency' => encrypt(1)
                ]
            );
        }

        $creator = [
            'type' => 'commerce_id',
            'id' => $commerce,
            'userId' => Auth::id()
        ];

        $player = null;

        $viewData = self::getGamesRoomsView(
            $creator,
            ['player' => $player, 'currency' => decrypt($currency), 'roomType' => $roomType]
        );


        // Retorna a vista con la informacion
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function gamesRoomsCustomer($customerId, $userToken, $currency = '')
    {
        // Si el token de usuario es diferente de 'available-rooms'
        if ($userToken !== 'available-rooms') {
            $customer = $this->validateCustomer($customerId, $userToken);

            // Verifica si hubo error en la validación del cliente
            if (isset($customer['Error'])) {
                abort(404);
            }

            $customerUser = CustomerUser::getByCustomer($customer, $userToken, ['id', 'currency']);
            $currency = CustomerExchangeRate::getCustomerCurrencyValue($customer, $customerUser->currency, 'id');

            $player = $customerUser->id;
        } else {
            $customer = Customer::getValueByCustomerID($customerId, 'id');
            $currency = decrypt($currency);
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
            'currency' => $currency
        ]);

        // Retorna la vista con la información
        return $this->handleViewData($viewData, $creator['type']);
    }

    private function handleCartonByCreator($creator, $roomCtnPrice, $data, $request)
    {
        $numOfCtns = $data['numOfCtns'];
        $exchangeRate = 1;
        $saleAmount = 0;

        $creatorType = $creator['type'];

        $player = $request->player ?? null ? decrypt($request->player) : null;

        if ($creatorType === 'commerce_id') {
            $currency = decrypt($request->currency);
        } else if ($creatorType === 'customer') {
            $user = CustomerUser::getById($player, ['token', 'currency']);
            $currency = $user->currency;
        }

        $creatorCtnPrice = BingoCardPrice::getValueCtnPrice($data['room'], $currency, $creator['userId']);

        if ($creatorType === 'customer') {

            $customerResponse = $this->getUserBalanceFromCustomer($creator['id'], $user->token);

            if (isset($customerResponse['Error'])) {
                Log::error('Error al generar Carton del Cliente/Jugador: ' . $customerResponse['Error']);
                return ['Error' => 'Ha ocurrido un error en la solicitud'];
            }

            $exchangeRate = CustomerExchangeRate::getCustomerCurrencyValue($creator['id'], $currency);
            $saleAmount = ($creatorCtnPrice ?? $roomCtnPrice * $exchangeRate) * $numOfCtns;

            if ($customerResponse['Data'][0]['Amount'] < $saleAmount) {
                return ['Error' => 'Saldo insuficiente'];
            }

            $data += [
                'currency' => $currency,
                'customer' => $creator['id'],
                'playerUser' => $player ?? null,
                'exchangeRate' => $exchangeRate,
            ];
        } else {
            $data['currency'] = $currency;
            $data['commerce_id'] = $creator['id'];

            if ($data['isLogged']) {
                $data['playerUser'] = $player;
                $columnBalance = $data['currency'] == 1 ? 'usdt' : 'balance';
                $exchangeRate = CommerceExchangeRate::getCommerceCurrencyValue($creator['id'], $data['currency']);
                $saleAmount = ($creatorCtnPrice ?? $roomCtnPrice * $exchangeRate) * $numOfCtns;

                if (Wallet::getValue($player, $columnBalance) < $saleAmount) {
                    return ['Error' => 'Saldo insuficiente'];
                }

                $data['exchangeRate'] = round($exchangeRate, 2);
            } else {
                if ($request->purchase_code && !UserPurchaseCodes::existsPurchaseCode($request->purchase_code)) {
                    return ['Error' => 'Código de Compra no existe'];
                }

                if ($player) {
                    $data += [
                        'country' => $request->country ?? null,
                        'playerPhone' => $request->phone ?? null,
                        'playerName' => $request->name ?? null,
                        'playerLastName' => $request->last_name ?? null,
                    ];
                }
            }
        }

        return  ['data' => $data, 'creatorCtnPrice' => $creatorCtnPrice];
    }

    private function handleCartonBySeller($data, $request)
    {
        $seller = Seller::getByUserId(Auth::id(), ['id', 'commerce_id']);
        $commerce = $seller->commerce;

        $creator = [
            'type' => 'commerce_id',
            'id' => $commerce,
            'userId' => Commerce::getValueById($commerce, 'user')
        ];

        $data['creator'] = $creator;

        $data['commerce_id'] = $commerce;
        $data['seller'] = $seller->id;
        $data['country'] = $request->country;
        $data['currency'] = Currency::getValueByCountry($request->country, 'id');

        $playerPhone = $request->phone ?? null;
        $data['playerPhone'] = $playerPhone;
        $data['playerName'] = $request->name ?? null;
        $data['playerLastName'] = $request->last_name ?? null;

        $roomUser = ExternalPlayers::select('id', 'name', 'lastname', 'link')
            ->where('game', 1)->where('room', $data['room'])
            ->where(function ($query) use ($playerPhone) {
                $query->where('phone', $playerPhone);
            })->value('room_user');

        if ($roomUser) {
            $data['roomUser'] = $roomUser;
        }

        $arrInitials = $this->initialCharacters([$data['playerName'], $data['playerLastName']]);
        $data['initials'] =  $arrInitials[0] . $arrInitials[1];

        $data['exchangeRate'] = round(CommerceExchangeRate::getCommerceCurrencyValue($commerce, $data['currency']), 2);

        return $data;
    }

    public function generateCarton(Request $request)
    {
        $room = BingoRoom::select('id', 'status', 'prize_pool')
            ->where('encrypted', $request->room)->first();

        if (!$room || ($room && $room->status > 2)) {
            return redirect()->back()->with('message', 'Sala no encontrada')->with('type', 'warning');
        }

        $data = ['room' => $room->id, 'numOfCtns' => $request->num_ctns ?? 1];

        $roomCtnPrice = $room->Config->card_price;
        $creatorType = $request->creator_type ?? false;

        if ($creatorType) {

            $creatorId = decrypt($request->creator_id);
            $creatorUser = '';

            switch ($creatorType) {
                case 'admin':
                    # code...
                    break;
                case 'commerce_id':
                    $creatorUser = Commerce::getValueById($creatorId, 'user');
                    break;
                case 'customer':
                    $creatorUser = Customer::getValueById($creatorId, 'user');
                    break;
                default:
                    # code...
                    break;
            }

            $creator = [
                'type' => $creatorType,
                'id' => $creatorId,
                'userId' => $creatorUser
            ];

            // Adicionar datos al conjunto
            $data['creator'] = $creator;
            $data['isLogged'] = $request->is_login ?? false;

            $response = $this->handleCartonByCreator($creator, $roomCtnPrice, $data, $request);

            if (isset($response['Error'])) {
                return response()->json(['type' => 'warning', 'message' => $response['Error']]);
            }

            $data = $response['data'];
            $creatorCtnPrice = $response['creatorCtnPrice'];
        } else {
            $data = $this->handleCartonBySeller($data, $request);
            $creatorCtnPrice = BingoCardPrice::getValueCtnPrice($room->id, $data['currency'], $data['creator']['userId']);
        }

        // Definir precio del carton
        $ctnPrice = $creatorCtnPrice
            ? $this->calculateCtnPrice($creatorCtnPrice, true)
            : $this->calculateCtnPrice($roomCtnPrice, false, $data['exchangeRate']);

        $data['ctnPrice'] = $ctnPrice;

        // Iniciar Transaccion
        DB::beginTransaction();

        try {

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

            if (isset($data['commerce_id']) || isset($data['seller'])) {
                // Usuario Fuera de Sesion
                if (!isset($data['isLogged'])) {
                    if (!isset($data['roomUser'])) $data['roomUser'] = $cartonStore;
                    $data = $this->handleExternalPlayers($data);
                }
            }

            if ($creatorType) {

                if ($creatorType === 'customer') {
                    // Validar transacciones del cliente
                    app(HandleTransactions::class)->validateTransactions($creator['id']);
                }

                $prizePool = $this->convertPrizeValue($data['room'], $data['currency'], $room->prize_pool);
                $ctns = BingoRoomUser::getValue($cartonStore, 'ctns');

                DB::commit(); // Confirmar transacción

                return response()->json([
                    'prizePool' => $prizePool,
                    'ctns' => $ctns,
                    'type' => 'success',
                    'message' => 'Proceso efectuado con éxito'
                ]);
            }

            DB::commit(); // Confirmar transacción

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
        $game = GameRule::getByGame('BINGO', $creatorUser, ['image', 'encrypted']);
        $image = false;

        if ($game) {
            $image = "bingo-rules/$game->encrypted/$game->image";
        }

        return $image;
    }

    public function getBingoRules($creatorUser)
    {
        $rules = GameRule::getValueByGame('BINGO', $creatorUser);

        return $rules ?: view("includes.info.game.bingo");
    }

    private function getGameInfo($creator, $data = [])
    {
        $creatorUser = $creator['userId'];

        switch ($creator['type']) {

            case 'customer':

                $creatorId = $creator['id'];

                // Obtener informacion de Sala
                $room = BingoRoom::select('id', 'status')
                    ->where('creator_user_id', $creatorUser)
                    ->where('encrypted', $data['room'])
                    ->whereIn('status', [2, 3, 4])->first();

                // Si la sala no existe
                if (!$room) return ['error' => 'Sala no disponible'];

                $roomId = $room->id;
                $isLogged = false;

                $customer = Customer::getById($creatorId, ['name', 'customer_id', 'logo', 'token']);
                $customerId = $customer->customer_id;
                $userToken = $customer->token;

                $moreViewData = ['customerLogo' => "storage/customers/$userToken/$customer->logo"];

                if (isset($data['userToken'])) {
                    $userToken = $data['userToken'];
                    $user = CustomerUser::getValueByToken($creatorId, $userToken, ['user_id', 'currency']);

                    $currencySym = $user->currency;
                    $roomUser = BingoRoomUser::getValueByPlayerUser($room->id, $creatorUser, $user->user_id);
                } else {
                    $currencyId = $data['currency'];
                    $currencySym = CustomerExchangeRate::getValueById($currencyId, 'currency');
                }

                $customer = Customer::getById($creatorId, ['name', 'customer_id', 'logo', 'token']);

                $moreViewData['player'] = isset($roomUser) ? encrypt($roomUser) : false;

                if ($room->status == 2) {
                    $moreViewData['customerName'] = $customer->name;
                }

                $closingRoute = route(
                    'bingo.game-rooms-customer',
                    ['customer' => $customerId, 'user' => $userToken]
                );
                break;
            case 'RKE';
            case 'commerce_id':

                $creatorId = $creator['id'];

                if (isset($data['playerLink'])) {

                    $playerLink = ExternalPlayers::getByLink($data['playerLink'], ['room', 'room_user', 'country']);

                    if (!$playerLink) return ['error' => 'Sala no encontrada'];

                    $roomId = $playerLink->room;

                    // Obtener informacion de Sala
                    $room = BingoRoom::where('id', $roomId)->whereIn('status', [2, 3, 4])->exists();

                    if (!$room) return ['error' => 'Sala no disponible'];

                    $isLogged = false;

                    $roomUser = $playerLink->room_user;

                    $currency = Currency::getCurrencyByCountry($playerLink->country, ['id', 'symbol']);
                    $currencyId = $currency->id;
                    $currencySym = $currency->symbol;

                    $currenciesAvailable = Currency::getById($currencyId, ['id', 'symbol', 'description']);
                } else {
                    $roomId = BingoRoom::getValueByEncrypted($data['room'], 'id');

                    // Obtener informacion de Sala
                    $room = BingoRoom::where('id', $roomId)->whereIn('status', [2, 3, 4])->exists();

                    if (!$room) return ['error' => 'Sala no disponible'];

                    if (isset($data['player'])) {
                        $isLogged = true;
                        $player = decrypt($data['player']);
                        $currencyId = Wallet::getValue($player, 'currency') ?? 1;
                        $currencySym = Currency::getValue($currencyId, 'symbol');

                        $currenciesAvailable = Currency::getById($currencyId, ['id', 'symbol', 'description']);

                        $roomUser =  BingoRoomUser::getValueByColumn($roomId, 'player_user', $player, 'id');
                    } else {
                        $isLogged = false;
                        $currencyId = $data['currency'];
                        $currencySym = Currency::getValue($currencyId, 'symbol');

                        $commerceCurrenciesAvailable = CommerceExchangeRate::getCurrenciesByCommerce($creatorId, 1);
                        $currenciesAvailable = Currency::getCurrenciesById($commerceCurrenciesAvailable, ['id', 'symbol', 'description']);
                    }
                }

                $moreViewData = [
                    'player' => isset($roomUser) ? encrypt($roomUser) : false,
                    'saleLink' => true,
                    'currenciesAvailable' => $currenciesAvailable ?? []
                ];

                $bingoCreatorUser = BingoCreator::getValueByCreatorRoom($creator, $roomId, 'creator_user_id');
                // Definir precio del carton
                $bingoCtnPrice = BingoCardPrice::getValueCtnPrice($roomId, $currencyId, $bingoCreatorUser)
                    ?? CommerceExchangeRate::getCommerceCurrencyValue($creatorId, $currencyId);

                $moreViewData['ctnPrice'] = $this->formatAmount($bingoCtnPrice, 'float');

                $closingRoute = false;
                break;
            default:
                return false;
                break;
        }

        // Verificar Cierre de Sala
        (new HandleBingoRoomsService())->checkRoomClosure($roomId);

        $room = BingoRoom::with('Config:room,date,time,unix_date,prize_pool_type,rounds')
            ->select('id', 'status', 'encrypted')->where('id', $roomId)->first();

        if (!in_array($room->status, [2, 3, 4])) return ['error' => 'Sala no disponible'];

        $bingoRound = BingoRound::getRoundInProgress($roomId, ['round', 'start_time', 'bingo_pattern_id']);

        if ($bingoRound) {
            $round = $bingoRound->round;
            $starTime = $bingoRound->start_time;
            $bingoPattern = 'CARTON LLENO';
            if ($bingoRound->bingo_pattern_id > 1) {
                $bingoPattern = BingoPatterns::getValueById($bingoRound->bingo_pattern_id, 'name');
            }
        } else {
            $round = 1;
            $starTime = $room->Config->unix_date;
            $bingoPattern = false;
        }

        date_default_timezone_set(config('app.timezone'));

        $vanDraw = ($room->status > 2) ?
            BingoDraw::select('id', 'chip')->where('room', $roomId)
            ->where('round', $round)->where('status', 3)->first() : [];

        $draw = ($room->status == 3) ?
            BingoDraw::where('room', $roomId)->where('round', $round)->where('id', '<=', $vanDraw->id)
            ->where('time', '>', time())->get(['code', 'time']) : [];

        $chipsRest = ($room->status == 3) ?
            BingoDraw::select('chip')->where('room', $roomId)->where('round', $round)
            ->where('time', '>', time())->where('id', '<=', $vanDraw->id)
            ->orderBy('chip')->pluck('chip')->sort()->toArray() : [];

        $codeEncrypt = ($room->status == 3) ?
            BingoDrawCode::select('sym')->where('room', $roomId)
            ->pluck('sym')->toArray() : [];

        $dataChips = $this->getChips($room->encrypted);

        if (isset($roomUser)) {
            // Obtener Cartones del ID jugador-sala
            $ctns = BingoCtns::getCtnsByPlayerInRoom($roomId, 'room_user', $roomUser);
        }

        $infoGame = [];

        if ($room->status >= 3) {
            // Genera la llave random de 32 bytes codificada en base64
            $randomKey = base64_encode(random_bytes(32));

            $infoGame = $this->getResult(
                ['room' => $room->encrypted, 'currency' => $currencyId],
                $roomUser ?? null,
                $randomKey
            );

            // Guarda la llave en sesión para poder recuperarla desde frontend
            session(['r4m60nk3y' => $randomKey]);
        }

        $starTime = $starTime ?? $this->convertToUnixTimestamp($room->Config->date, $room->Config->time);

        $prizePool = BingoRoom::getValueById($roomId, 'prize_pool');

        $viewData = [
            'bladeLayout' => $isLogged ? 'layouts.auth-layout' : 'layouts.guest-layout',
            'title' => 'BingoCash',
            'isLogged' => $isLogged,
            'creatorType' => $creator['type'],
            'creatorId' => encrypt($creatorId),
            'room' => $room,
            'round' => $round,
            'prizePoolType' => $room->Config->prize_pool_type == 1 ? 'Bote' : 'Premio',
            'currencySymbol' => $currencySym,
            'prizePool' => $this->convertPrizeValue($roomId, $currencyId, $prizePool),
            'bingoPattern' => $bingoPattern,
            'startTime' => $starTime,
            'chipsRest' => $chipsRest,
            'cartons' => $ctns ?? [],
            'draw' => $draw,
            'encrypt' => $codeEncrypt,
            'allChips' => $dataChips['allChips'],
            'vanDraw' => $vanDraw ? (int) $vanDraw->chip : '',
            'infoGame' => $infoGame,
            'timeServ' => $dataChips['timeServ'],
            'imageBingoRules' => $this->getImageBingoRules($creatorUser),
            'bingoRules' => $this->getBingoRules($creatorUser),
            'closingRoute' => $closingRoute
        ];

        if ($room->status == 2) $viewData['currencyId'] = encrypt($currencyId);

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.game.bingo.in-game', 'data' => $viewData];
    }

    public function inGame($player, $room = '')
    {
        // Vista para el juego de la venta conjunta de todos los comercios
        $player = ExternalPlayers::getByLink($player, ['room', 'creator_user_id', 'link', 'currency']);

        if (!$player) abort(404); // Retorna un error 404 Not Found

        $creatorUser = $player->creator_user;
        $userCreator = User::getCreatorUser($creatorUser, false, true);

        if (!$player) abort(404); // Retorna un error 404 Not Found

        $creator = [
            'type' => $userCreator['type'],
            'id' => $userCreator['id'],
            'userId' => $creatorUser
        ];

        $viewData = self::getGameInfo($creator, [
            'playerLink' => $player->link,
            'room' => $player->room,
            'currency' => $player->currency
        ]);

        // Retorna a vista con la informacion
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function inGameCustomer($customerId, $userToken, $room)
    {
        // Validar Informacion de cliente-user
        $response = $this->validateCustomer($customerId, $userToken);

        if (isset($response['Error'])) {
            abort(404);
        }

        $customer = Customer::getByCustomerID($customerId, ['id', 'user']);

        $creator = [
            'type' => 'customer',
            'id' => $customer->id,
            'userId' => $customer->user
        ];

        // Obtener informacion de la vista del cliente
        $viewData = self::getGameInfo($creator, ['room' => $room, 'userToken' => $userToken]);

        // Retorna a vista con la informacion
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function outGame($uuidCode, $room = '', $currency = '')
    {
        // Vista del el juego solo para comprar [TODOS]
        $code = UniqueCode::getByCode($uuidCode);

        $creatorUser = $code->user;

        $creator = [
            'type' => RoleRepository::getValueById($code->user_type, 'prefix'),
            'id' => User::getCreatorUser($creatorUser),
            'userId' => $creatorUser
        ];

        $viewData = self::getGameInfo($creator, ['room' => $room, 'currency' => decrypt($currency)]);

        // Retorna a vista con la informacion
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function outGameCommerce($commerce, $room, $currency)
    {
        // Vista del el juego para un comercio especifico solo para comprar
        $commerce = Commerce::getValueByCodeUUID($commerce, 'id') ?? decrypt($commerce);

        // Redireccionar al Login si no hay coincidencia
        if (!$commerce) return redirect()->back();

        $creator = [
            'type' => 'commerce_id',
            'id' => $commerce,
            'userId' => Commerce::getValueById($commerce, 'user')
        ];

        $viewData = self::getGameInfo($creator, ['room' => $room, 'currency' => decrypt($currency)]);

        // Retorna a vista con la informacion
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function outGameCustomer($customer, $room, $currency)
    {
        // Vista del el juego para un cliente especifico para comprar y/o visualizar
        $customer = Customer::getById(decrypt($customer), ['id', 'user']);

        // Redireccionar a la vista anterior
        if (!$customer) return redirect()->back();

        $creator = ['type' => 'customer', 'id' => $customer->id, 'userId' => $customer->user];

        $viewData = self::getGameInfo($creator, ['room' => $room, 'currency' => decrypt($currency)]);

        // Retorna a vista con la informacion
        return $this->handleViewData($viewData, $creator['type']);
    }

    private function hasPurchasedCards($phone)
    {
        ExternalPlayers::where('phone', $phone)->first();
        return true;
    }

    public function buyCardsToTheCommerce(Request $request)
    {
        // Commerce::

        $hasCtns = $this->hasPurchasedCards($request->phone);

        if ($hasCtns) return response()->json(['room' => [], 'config' => []]);

        $this->cartonStore(array());
    }

    public function getChips($room)
    {
        $room = BingoRoom::getByEncrypted($room, ['id', 'status']);

        if (!$room) ['Error' => 'Sala no encontrada'];

        $round = BingoRound::getValueByRoundInProgress($room->id, 'round');

        $chipVanID = BingoDraw::where('room', $room->id)
            ->where('round', $round)->where('status', 3)->value('id');

        if ($chipVanID) {
            date_default_timezone_set(config('app.timezone'));

            // Realizamos una única consulta para obtener todas las fichas en las condiciones especificadas
            $allChips = array_map('intval', BingoDraw::select('chip')->where('room', $room->id)
                ->where('round', $round)->where('id', '<=', $chipVanID)->where('time', '<=', time())
                ->orderByDesc('id')->pluck('chip')->toArray());
        }

        return ['allChips' => $allChips ?? [], 'timeServ' => time()];
    }

    public function getResult(array $data, $roomUser, $randomKey)
    {
        // Obtener la información de los ganadores
        $winnersInfo = self::getInfoWinners($data, $roomUser);

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

    private function getInfoRooms($creatorUser, $roomType, $currency, $otherCommerce = false)
    {
        $gameId = Game::getValueByName('BINGO');
        $roomType = $roomType === 'private-rooms' ? 'PV' : 'PB';
        $operator = $otherCommerce ? '<>' : '=';

        $relations = [
            'Config.PrizePoolType',
            'Config:room,game_mode,prize_pool_type,card_price,date,time,unix_date',
            'Config.GameMode:id,description',
            'GameExchangeRate' => fn($query) => $query->where('currency', $currency)->where('game', $gameId),
        ];

        if ($otherCommerce) {
            $relations['Creator'] = fn($q) => $q->select('id', 'room')
                ->where('creator_user_id', $creatorUser)->where('status', 1);

            $relations['CtnsLot'] = fn($q) => $q->select('id', 'room', 'exchange_rate', 'sale_amount')
                ->where('creator_user_id', $creatorUser);
        } else {
            $relations['Creator'] = fn($query) => $query->select('id', 'room');
        }

        $query = BingoRoom::with($relations)
            ->withCount([
                'PlayersRoom as winners' => function ($query) {
                    $query->where('winner', '<>', '0');
                }
            ])
            ->where('creator_user_id', $operator, $creatorUser)
            ->where('room_type', $roomType);

        if ($otherCommerce) {
            $query->whereHas('Creator', function ($q) use ($creatorUser) {
                $q->where('creator_user_id', $creatorUser)->where('status', 1);
            });
        }

        return $query->orderBy('status')
            ->orderByRaw("(SELECT CONCAT(date, ' ', time) FROM bingo_rooms_config WHERE bingo_rooms_config.room = bingo_rooms.id LIMIT 1)")
            ->get();
    }

    private function getGameRoomsInfo($creator, $currency, $roomType)
    {
        //app(HandleBingoRoomsService::class)->checkRoomClosure();

        $view = $roomType === 'private-rooms' ? 'private' : 'public';

        switch ($creator['type']) {
            case 'admin': // Admin
                break;
            case 'commerce_id': // Comercio
                $creatorId = decrypt($creator['id']);

                $isLogged = true;
                $currencySym = Currency::getValue($currency, 'symbol');
                $exchangeRate = CommerceExchangeRate::getCommerceCurrencyValue($creatorId, $currency);

                $currencies = Currency::getCurrenciesById(
                    [...CommerceExchangeRate::getCurrenciesByCommerce($creatorId), 1],
                    ['id', 'iso_code', 'name']
                );

                $routeWithCurrency = [
                    'route' => 'bingo.room-summary',
                    'parameters' => [
                        'roomType' => $roomType,
                        'creatorType' => 'commerce_id',
                        'creatorId' => encrypt($creator['id'])
                    ]
                ];

                $gameRoomRoute =  route('bingo.gameRoomsCommerce', ['roomType' => $view, 'currency' => encrypt($currency)]);

                $moreViewData = [
                    'uuidCode' => Commerce::getValueById($creatorId, 'uuid_code'),
                    'countries' => Country::getAllAvailableCountries()
                ];

                break;
            case 'customer': // Cliente

                $creatorId = decrypt($creator['id']);
                $isLogged = $returnBtn = false;

                $customerExchangeRate = CustomerExchangeRate::getById($currency);
                $exchangeRate = $customerExchangeRate->exchange_rate;
                $currencySym = $customerExchangeRate->currency;
                $currencies = CustomerExchangeRate::getCurrenciesIDsByCustomer($creatorId);

                $customer = Customer::getById($creatorId, ['customer_id', 'token']);

                $routeWithCurrency = [
                    'route' => 'bingo.room-summary',
                    'parameters' => [
                        'roomType' => $roomType,
                        'creatorType' => 'customer',
                        'creatorId' => encrypt($creator['id'])
                    ]
                ];

                $gameRoomRoute =  route(
                    'bingo.game-rooms-customer',
                    [
                        'customer' => $customer->customer_id,
                        'user' => 'available-rooms',
                        'currency' => encrypt($currency)
                    ]
                );

                $gameId = Game::getValueByName('BINGO');

                $customerGameID = CustomerGame::getByGameID($creatorId, $gameId) ?? null;

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

        $creatorUser = $creator['userId'];

        $rooms = $this->getInfoRooms($creatorUser, $roomType, $currency);

        $bingoPatterns = BingoPatterns::getAllByUser($creatorUser, ['id', 'name'], 'ACTIVE');

        $bingoPatterns = BingoPatterns::where(function ($query) use ($creatorUser) {
            $query->where('user', $creatorUser)
                ->orWhereNull('user');
        })->where('status', '<>', 'DELETED')->get();

        $timeZoneID = User::getValueById($creatorUser, 'time_zone');
        $timeZone = TimeZone::getById($timeZoneID, ['name', 'utc']);

        $viewData = [
            'bladeLayout' => $isLogged ? 'layouts.auth-layout' : 'layouts.guest-layout',
            'title' => 'Salas de Bingo',
            'subtitle' => $view === 'private' ? 'Privadas' : 'Publicas',
            'view' => strtoupper($view),
            'name' => 'bingo',
            'returnBtn' => $returnBtn ?? true,
            'routeWithCurrency' => $routeWithCurrency,
            'routeRKE' => $this->routeService->getBaseUrl(),
            'creator' => $creator,
            'currencies' => $currencies,
            'currency' => $currency,
            'currencySymbol' => $currencySym,
            'exchangeRate' => $exchangeRate ?? 1,
            'roomType' => $view === 'private' ? 'PV' : 'PB',
            'bingoPatterns' => $bingoPatterns,
            'timeZone' => $timeZone,
            'rooms' => $rooms,
            'gameRoomRoute' => $gameRoomRoute,
            'activeRooms' => $rooms->whereIn('status', [2, 3])->count(),
            'typesPrizePool' => PrizePoolType::all(),
            'gameModes' => GameModes::all()
        ];

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.game.bingo.summary.index', 'data' => $viewData];
    }

    /**
     * Maneja la lógica para mostrar el resumen (listado) de salas de juego según tipo de sala y creador.
     *
     * @param string $roomType    Tipo de sala: 'automatic-rooms', 'private-rooms', 'public-rooms', 'other-public-rooms'.
     * @param string $creatorType Tipo de creador: 'admin', 'commerce_id', 'customer'.
     * @param mixed  $creatorId   ID del creador (puede estar vacío si se determina automáticamente).
     * @param mixed  $currency        Moneda seleccionada (opcional, por defecto 1).
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function handleGameRoomSummary($roomType, $creatorType, $creatorId = '', $currency = '')
    {
        // Validar tipo de sala
        if (!$this->roomTypeService->isValid($roomType)) {
            return redirect()->back();
        }

        // Atajos para tipos que se manejan directamente
        if ($roomType === 'automatic-rooms') {
            return $this->allAutomaticRooms($creatorType, $creatorId);
        }
        if ($roomType === 'other-public-rooms') {
            return $this->allOtherPublicRooms($creatorType, $creatorId, $currency);
        }
        // Caso especial: si es cliente
        if ($creatorType === 'customer') {
            return $this->handleCustomerRooms($roomType, $creatorId, $currency);
        }
        // Determinar ID del creador según tipo
        $creatorId = match ($creatorType) {
            'admin' => $creatorId,
            'commerce_id' => Commerce::getValueByUserId(Auth::id(), 'id'),
            default => null
        };

        // Si el tipo no es válido o no hay creador, error
        if (!$creatorId) {
            return redirect()->back()->with('error', "El $creatorType no existe o no está vinculado al usuario.");
        }

        // Si falta la moneda, redirigir con moneda por defecto
        if (!$currency) {
            return redirect()->route('bingo.room-summary', [
                'roomType' => $roomType,
                'creatorType' => $creatorType,
                'creatorId' => encrypt($creatorId),
                'currency' => encrypt(1),
            ]);
        }

        // Preparar datos del creador
        $creator = [
            'type' => $creatorType,
            'id' => encrypt($creatorId),
            'userId' => Auth::id()
        ];

        // Obtener la información del resumen (listado) de las salas de juego
        $viewData = $this->getGameRoomsInfo($creator, $currency, $roomType);

        // Retornar la vista con la información
        return $this->handleViewData($viewData, $creatorType);
    }

    /**
     * Gestiona la carga de salas de juego para clientes.
     *
     * Esta función maneja tanto clientes logueados como clientes que acceden mediante token.
     * Valida la existencia del cliente, la moneda seleccionada, y redirige automáticamente
     * cuando es necesario (por ejemplo, si no se ha especificado una moneda).
     *
     * Flujo principal:
     *  - Determina si el cliente está logueado o accediendo por token.
     *  - Valida la moneda; si no se proporciona, selecciona la primera disponible.
     *  - Intenta desencriptar la moneda; si falla, valida que exista para el cliente.
     *  - Obtiene la información de las salas y devuelve la vista correspondiente.
     *
     * @param string $roomType   Tipo de sala.
     * @param mixed  $creatorId  ID del cliente o token.
     * @param mixed  $currency       ID o token de la moneda (opcional).
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    private function handleCustomerRooms($roomType, $creatorId = '', $currency = '')
    {
        // Buscar cliente logueado
        $customer = Customer::getByUserId(Auth::id(), ['id', 'user']);

        if (!$customer) {
            // Cliente no logueado → buscar por token
            $isLogged = false;
            $customerId = Customer::getValueByToken($creatorId, 'id');

            if (!$customerId) {
                return response()->json(['message' => 'Token del cliente no existe'], 404);
            }
        } else {
            // Cliente logueado
            $isLogged = true;
            $customerId = $customer->id;
        }

        // Si no se proporciona moneda → obtener la primera disponible
        if (!$currency) {
            $currencies = CustomerExchangeRate::getCurrenciesIDsByCustomer($customerId);

            if (empty($currencies)) {
                return response()->json(['message' => 'No se encontraron monedas registradas'], 404);
            }

            return redirect()->route('bingo.room-summary', [
                'roomType' => $roomType,
                'creatorType' => 'customer',
                'creatorId' => $isLogged ? encrypt($customerId) : $customerId,
                'currency' => encrypt($currencies[0]->id)
            ]);
        }

        // Intentar desencriptar la moneda
        try {
            $currency = decrypt($currency);
        } catch (\Exception $e) {
            // Si no se puede desencriptar, validar que la moneda exista para el cliente
            if (!CustomerExchangeRate::existsCurrencyByCustomer($customerId, $currency)) {
                return response()->json(['message' => 'Divisa inválida'], 404);
            }

            // Obtener valor interno de la moneda
            $currency = CustomerExchangeRate::getCustomerCurrencyValue($customerId, $currency, 'id');

            // Redirigir con la moneda correctamente formateada
            return redirect()->route('bingo.room-summary', [
                'roomType' => $roomType,
                'creatorType' => 'customer',
                'creatorId' => $isLogged ? encrypt($customerId) : $customerId,
            ]);
        }

        // Datos del creador para obtener las salas
        $creator = [
            'type' => 'customer',
            'id' => encrypt($customerId),
            'userId' => $customer->user ?? null
        ];

        // Obtener información de salas
        $viewData = $this->getGameRoomsInfo($creator, $currency, $roomType);

        // Retornar la vista final
        return $this->handleViewData($viewData, $creator['type']);
    }

    /**
     * Crea una nueva sala de Bingo con su configuración y registros asociados.
     *
     * Flujo:
     *  1. Valida el tipo de creador.
     *  2. Prepara datos base para la sala y su configuración.
     *  3. Crea la sala, su configuración, y registra al creador.
     *  4. Maneja premios por rondas y monedas de precio por cartón si aplica.
     *  5. Usa transacciones para asegurar consistencia.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        // --- 1. Validar tipo de creador ---
        $creator = json_decode($request->creator);
        $creatorType = $creator->type ?? null;
        $creatorId = isset($creator->id) ? decrypt($creator->id) : null;

        $allowedPrefixes = ['admin', 'commerce_id', 'customer'];
        if (!in_array($creatorType, $allowedPrefixes)) {
            return redirect()->back()->with('msg-error', 'No tienes permiso para crear una sala');
        }

        // --- 2. Datos iniciales ---
        $userId = Auth::id();
        $amountAddedPrizePool = $request->amount_added_prize_pool;
        $boat = $request->prize_pool_type > 1 ? $amountAddedPrizePool : 0;
        $model = 'BingoRoom';

        $createData = [
            'encrypted' => $this->generateUniqueCode($model),
            'status' => 1,
            'room_type' => $request->room_type,
            'creator_user_id' => $userId,
            'prize_pool' => $boat,
            'uuid_code' => $this->getCodeUUID($model)
        ];

        date_default_timezone_set(config('app.timezone'));

        // --- 3. Transacción ---
        DB::beginTransaction();
        try {
            // Comisión por defecto
            $bingoRKECommission = GameConfig::getGameParamValue('BINGO', 'COMMISSION') ?? 5;

            // Crear sala
            $roomId = BingoRoom::create($createData)->id;

            // Configuración de la sala
            $dataConfig = [
                'room' => $roomId,
                'creation' => 'M',
                'creator_user_id' => $userId,
                'game_mode' => $request->game_mode,
                'date' => $request->date,
                'time' => $request->time,
                'unix_date' => strtotime("{$request->date} {$request->time}"),
                'card_price' => $request->card_price,
                'allowed_currencies' => $request->allowed_currencies,
                'rke_commn' => $bingoRKECommission,
                'room_commn' => $request->room_commn,
                'prize_pool_type' => $request->prize_pool_type,
                'amount_added_prize_pool' => $amountAddedPrizePool,
                'time_chip' => $request->time_chip,
                'rounds' => $request->rounds,
                'rounds_pct' => $request->rounds_pct ?? null,
                'serials_pct' => $request->serials_pct,
                'num_serials' => $request->num_serials,
                'jackpot_pct' => $request->jackpot_pct,
                'plays_pct' => null,
                'bingo_patterns' => null
            ];
            BingoRoomConfig::create($dataConfig);

            // Registrar creador de la sala
            $creatorTypeID = Role::getValueByPrefix($creatorType, 'id');
            BingoCreator::create([
                'room' => $roomId,
                'creator_user_id' => $userId,
                'creator_type' => $creatorTypeID,
                'creator_id' => $creatorId,
                'creator_commn' => $request->room_commn,
                'card_price' => $request->card_price,
                'allowed_currencies' => $request->allowed_currencies
            ]);

            // --- 4. Premios y monedas adicionales ---
            $this->handleRoundsDrafts(
                'M',
                'CREATE',
                $request->rounds_prize_type ?? [],
                $request->rounds_bingo_patterns ?? [],
                $request->rounds_prize_value ?? [],
                $roomId
            );

            if ($request->card_price_currencies) {
                $this->handleCtnPriceCurrencies('M', 'CREATE', $request->card_price_currencies, $roomId, $userId);
            }

            // Confirmar transacción
            DB::commit();
            return redirect()->back()->with('message', 'Sala creada con éxito')->with('type', 'success');
        } catch (\Exception $e) {
            // Revertir si hay error
            DB::rollBack();
            Log::error('Error al crear la sala: ' . $e->getMessage());
            return redirect()->back()->with(['type' => 'warning', 'message' => 'Error al crear la sala']);
        }
    }

    private function handleRoundsDrafts(string $type, string $action, array $roundsPrizeType, array $roundsPlayType, array $roundsPrizeValue, int $roomId)
    {
        try {
            $columnRoom = $type === 'M' ? 'bingo_room_id' : 'automatic_bingo_room_id';
            $numRounds = count($roundsPlayType);
            $timestamp = now();

            // Definir datos comunes
            $data = [
                $columnRoom => $roomId,
                'rounds' => $numRounds,
                'prize_types' => ($numRounds === 1) ? array_fill(0, 1, 'percentage') : $roundsPrizeType,
                'bingo_patterns' => ($numRounds === 1) ? array_fill(0, 1, '1') : $roundsPlayType,
                'prize_values' => ($numRounds === 1) ? array_fill(0, 1, '100') : $roundsPrizeValue,
                'status' => 'ACTIVE',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            $existing = $type === 'M' ? BingoRoundDraft::getByRoom($roomId) : BingoRoundDraft::getByAutomaticRoom($roomId);

            if ($action === 'CREATE') {
                if (!$existing) {
                    BingoRoundDraft::create($data);
                } else {
                    throw new \Exception('Draft already exists for this room.');
                }
            } elseif ($action === 'UPDATE') {
                if ($existing) {
                    $existing->update([
                        'rounds' => $numRounds,
                        'prize_types' => $data['prize_types'],
                        'bingo_patterns' => $data['bingo_patterns'],
                        'prize_values' => $data['prize_values'],
                        'updated_at' => $timestamp,
                    ]);
                } else {
                    BingoRoundDraft::create($data);
                }
            } else {
                throw new \InvalidArgumentException("Invalid action: $action");
            }
        } catch (\Exception $e) {
            Log::error("Error en handleRoundsDrafts para BingoRoundDraft: " . $e->getMessage(), [
                'action' => $action,
                'roomId' => $roomId,
                'trace'  => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function handleCtnPriceCurrencies(string $type, $action, $ctnPriceCurrencies, $roomId, $creatorUser = '')
    {
        $column = $type === 'M' ? 'bingo_room_id' : 'automatic_bingo_room_id';
        $timestamp = now();

        // Filtrar y preparar arrays de monedas y precios
        $arrCurrencies = [];
        $arrCtnPrices = [];

        foreach ($ctnPriceCurrencies as $currency => $ctnPrice) {
            if ($ctnPrice) {
                $arrCurrencies[] = $currency;
                $arrCtnPrices[] = $ctnPrice;
            }
        }

        // No hay datos válidos que guardar
        if (empty($arrCurrencies) || empty($arrCtnPrices)) {
            return;
        }

        $data = [
            $column => $roomId,
            'currencies' => $arrCurrencies,
            'card_prices' => $arrCtnPrices,
        ];

        if ($creatorUser) {
            $data['creator_user_id'] = $creatorUser;
        }

        try {
            if ($action === 'CREATE') {
                $data['created_at'] = $timestamp;
                BingoCardPriceDraft::create($data);
                return;
            }

            // UPDATE
            $existing = $type === 'M'
                ? BingoCardPriceDraft::getByRoom($roomId)
                : BingoCardPriceDraft::getByAutomaticRoom($roomId);

            if (
                $existing &&
                (
                    $existing->currencies != $arrCurrencies ||
                    $existing->card_prices != $arrCtnPrices
                )
            ) {
                $existing->update($data);
            }
        } catch (\Exception $e) {
            Log::error('Error en handleCtnPriceCurrencies: ' . $e->getMessage(), [
                'action' => $action,
                'roomId' => $roomId,
                'creatorUser' => $creatorUser,
                'ctnPriceCurrencies' => $ctnPriceCurrencies,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function update(Request $request, $id)
    {
        $roomId = decrypt($id);
        $room = BingoRoom::findOrFail($roomId);

        $creatorUser = $this->getUserData()['userId'];

        $ctnPriceCurrencies = $request->card_price_currencies;

        if ($room->creator_user <> $creatorUser) {

            DB::beginTransaction();

            try {

                if ($ctnPriceCurrencies) {
                    $this->handleCtnPriceCurrencies('M', 'UPDATE', $ctnPriceCurrencies, $roomId, $creatorUser);
                }

                $bingoCreator = BingoCreator::select('id', 'creator_commn', 'card_price', 'allowed_currencies')
                    ->where('room', $roomId)->where('creator_user_id', $creatorUser)->first();

                if ($bingoCreator) {
                    $updates = [];

                    if ($bingoCreator->creator_commn != $request->room_commn) {
                        $updates['creator_commn'] = $request->room_commn;
                    }

                    if ($bingoCreator->card_price != $request->card_price) {
                        $updates['card_price'] = $request->card_price;
                    }

                    if ($bingoCreator->allowed_currencies != $request->allowed_currencies) {
                        $updates['allowed_currencies'] = $request->allowed_currencies;
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
        $roundsPct = $request->rounds_pct ?? null;


        $amountAddedPrizePool = $request->amount_added_prize_pool;

        $boat = $request->prize_pool_type > 1 ? $amountAddedPrizePool : 0;

        $updateDataRoom = [
            'room_type' => $request->room_type,
            'prize_pool' => $room->prize_pool + $boat
        ];

        $updateDataConfig = [
            'date' => $newDate,
            'time' => $newTime,
            'unix_date' => $unixDate,
            'card_price' => $request->card_price,
            'allowed_currencies' => $request->allowed_currencies,
            'room_commn' => $request->room_commn,
            'prize_pool_type' => $request->prize_pool_type,
            'amount_added_prize_pool' => $amountAddedPrizePool,
            'time_chip' => $request->time_chip,
            'rounds' => $request->rounds,
            'rounds_pct' => $roundsPct,
            'serials_pct' => $request->serials_pct,
            'num_serials' => $request->num_serials,
            'jackpot_pct' => $request->jackpot_pct,
            'plays_pct' => null,
            'bingo_patterns' => null
        ];

        // Solo añadir 'game_mode' si el prize_pool es 0
        if ($room->prize_pool == 0) {
            $updateDataConfig['game_mode'] = $request->game_mode;
        }

        DB::beginTransaction();

        try {
            // Actualizar el bingo con los datos construidos
            $room->update($updateDataRoom);

            BingoRoomConfig::where('room', $roomId)->update($updateDataConfig);

            $bingoCreator = BingoCreator::where('room', $roomId)->firstOrFail();

            $bingoCreator->update([
                'creator_commn' => $request->room_commn,
                'card_price' => $request->card_price,
                'allowed_currencies' => $request->allowed_currencies
            ]);

            $roundsPrizeType = $request->rounds_prize_type ?? [];
            $roundsPlayType = $request->rounds_bingo_patterns ?? [];
            $roundsPrizeValue = $request->rounds_prize_value ?? [];

            $this->handleRoundsDrafts('M', 'UPDATE', $roundsPrizeType, $roundsPlayType, $roundsPrizeValue, $roomId);

            if ($ctnPriceCurrencies) {
                $this->handleCtnPriceCurrencies('M', 'UPDATE', $ctnPriceCurrencies, $roomId);
            }

            DB::commit();

            return redirect()->back()->with('message', 'La información fue actualizada')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error a al actualizar la información de la sala: ' . $e->getMessage());
            return redirect()->back()->with(['type' => 'warning', 'message', 'Error al actualizar la información']);
        }
    }

    public function getRoundsConfig($room, string $type)
    {
        $query = BingoRoundDraft::select(
            'rounds',
            'prize_types',
            'bingo_patterns',
            'prize_values'
        );

        if ($type === 'M') {
            $roomId = BingoRoom::getValueByEncrypted($room, 'id');
            $query->where('bingo_room_id', $roomId);
        } else {
            $query->where('automatic_bingo_room_id', $room);
        }

        $bingoRounds = $query->where('status', 'ACTIVE')->first();

        // Retornar respuesta con los datos
        return response()->json($bingoRounds);
    }

    public function getCtnPriceForCurrency($creation, $creatorType, $creatorId, $room = '')
    {
        $creatorId = decrypt($creatorId);

        $currencies = match ($creatorType) {
            'admin' => Currency::where('id', '!=', 1)->distinct()->pluck('currency'),
            'commerce_id' => CommerceCurrency::getAvailableCurrencies($creatorId, 1),
            default => CustomerExchangeRate::getCurrenciesByCustomer($creatorId),
        };

        $currenciesCtnPrice = collect();

        if ($room) {
            $bingoCtnPrice = $creation === 'M'
                ? BingoCardPriceDraft::getByRoom(BingoRoom::getValueByEncrypted($room, 'id'), ['bingo_room_id', 'currencies', 'card_prices'])
                : BingoCardPriceDraft::getByAutomaticRoom($room, ['automatic_bingo_room_id', 'currencies', 'card_prices']);

            $ctnPriceCurrencies = $bingoCtnPrice->currencies ?? [];
            $ctnPriceValues = $bingoCtnPrice->card_prices ?? [];


            if (is_array($ctnPriceCurrencies) && is_array($ctnPriceValues) && count($ctnPriceCurrencies) === count($ctnPriceValues)) {
                foreach ($ctnPriceCurrencies as $i => $currency) {
                    $valMax = CommerceExchangeRate::getCommerceCurrencyValue($creatorId, $currency) ?? 1;

                    $currenciesCtnPrice->push([
                        'currency' => $currency,
                        'ctnPrice' => $ctnPriceValues[$i],
                        'valMax' => round($valMax * 50, 2),
                    ]);
                }
            }
        }

        // Convertir los precios presentes en un array con claves por moneda
        $presentCurrenciesData = $currenciesCtnPrice->mapWithKeys(function ($item) use ($creatorType) {
            $currency = $item['currency'];

            return [
                $currency => [
                    'currency' => $currency,
                    'isoCode' => $creatorType === 'customer' ? $currency : Currency::getValue($currency, 'iso_code'),
                    'ctnPrice' => $item['ctnPrice'],
                    'valMax' => $item['valMax'],
                ],
            ];
        });

        // Determinar qué monedas no están en el draft
        $presentKeys = $presentCurrenciesData->keys();
        $missingCurrencies = collect($currencies)->filter(fn($c) => !$presentKeys->contains($c));

        // Construir datos faltantes
        $missingCurrenciesData = $missingCurrencies->mapWithKeys(function ($currency) use ($creatorId, $creatorType) {
            $valMax = CommerceExchangeRate::getCommerceCurrencyValue($creatorId, $currency) ?? 1;

            return [
                $currency => [
                    'currency' => $currency,
                    'isoCode' => $creatorType === 'customer' ? $currency : Currency::getValue($currency, 'iso_code'),
                    'ctnPrice' => '',
                    'valMax' => round($valMax * 50, 2),
                ],
            ];
        });

        $arrCurrenciesCtnPrice = $presentCurrenciesData->merge($missingCurrenciesData);

        return response()->json([
            'hasPrice' => $presentCurrenciesData->isNotEmpty(),
            'ctnPrice' => $arrCurrenciesCtnPrice,
        ]);
    }

    public function configuration($userToken = false)
    {
        if ($userToken) {
            $userRole = 'customer';
            $userToken = true;
            $user = Customer::getValueByToken($userToken, 'user');
        } else {
            $user = Auth::id();
            $userRoleID = User::getValueById($user, 'user_type');
            $userRole = RoleRepository::getValueById($userRoleID, 'prefix');
        }

        if (!in_array($userRole, ['admin', 'commerce_id', 'customer'])) {
            return redirect()->back();
        }

        $game = GameRule::getByGame('BINGO', $user, ['id', 'rules', 'encrypted']);
        $rulesID = null;
        $gameRules = null;

        if ($game) {
            $rulesID = encrypt($game->id);
            $gameRules = $game->rules;
        }

        $viewData = [
            'bladeLayout' => !$userToken ? 'layouts.auth-layout' : 'layouts.guest-layout',
            'title' => 'Bingo Configuración',
            'userToken' => $userToken,
            'rulesID' => $rulesID,
            'gameRules' => $gameRules,
            'imageBingoRules' => $this->getImageBingoRules($user),
            'bingoRules' => $this->getBingoRules($user),
        ];

        $viewData = ['route' => 'modules.game.bingo.configuration.index', 'data' => $viewData];

        return $this->handleViewData($viewData, $userRole);
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

                $game = GameRule::findOrFail($rulesID);

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

                $gameId = Game::getValueByName('BINGO');

                $code = $this->generateUniqueCode('GameRule', 12);

                $image = null;

                if ($fileImage) {
                    $folder = 'game/bingo/bingo-rules';
                    $image = $this->saveImage($fileImage, $folder, $code);
                }

                GameRule::create([
                    'user' => Auth::id(),
                    'game' => $gameId,
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
                    $status = BingoPatterns::getValueById($playID, 'status');

                    // Verificar el valor actual de 'status' y actualizarlo
                    $newStatus = $status == 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

                    BingoPatterns::where('id', $playID)->update(['status' => $newStatus]);

                    DB::commit();

                    return redirect()->back()->with([
                        'type' => 'success',
                        'message' => 'Registro actualizado'
                    ]);
                } else if ($action === 'delete') {
                    BingoPatterns::where('id', $playID)->update(['status' => 'DELETED']);

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
        $existsName = BingoPatterns::existsBingoPlay('name', $namePlay, $user);
        if ($existsName) {
            return redirect()->back()->with([
                'type' => 'warning',
                'message' => 'El nombre de jugada ya esta en uso'
            ]);
        }
        // Verificar si el conjunto de casillas ya esta en uso
        $existsBoxes = BingoPatterns::existsBingoPlay('boxes', $bingoBoxes, $user);
        if ($existsBoxes) {
            return redirect()->back()->with([
                'type' => 'warning',
                'message' => 'Conjunto de casillas ya estan en uso'
            ]);
        }

        DB::beginTransaction();

        try {

            if (!empty($playID)) {

                $bingoPatterns = BingoPatterns::findOrFail($playID);

                $updateData = [];

                if ($bingoPatterns->name != $namePlay) {
                    $updateData['name'] = $namePlay;
                }

                if ($bingoPatterns->boxes != $bingoBoxes) {
                    $updateData['boxes'] = $bingoBoxes;
                }

                if (!empty($updateData)) {
                    $bingoPatterns->update($updateData);
                }
            } else {
                BingoPatterns::create([
                    // 'type_pattern' => 'UNIQUE',
                    'name' => $namePlay,
                    'user' => $user,
                    'boxes' => $bingoBoxes,
                    'status' => 'ACTIVE'
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

        $roomId = decrypt($id);

        $room = BingoRoom::select(
            'encrypted',
            'room_type',
            'prize_pool',
            'players'
        )->where('id', $roomId)->firstOrFail();

        $config = BingoRoomConfig::select(
            'game_mode',
            'date',
            'time',
            'card_price',
            'allowed_currencies',
            'room_commn',
            'prize_pool_type',
            'amount_added_prize_pool',
            'time_chip',
            'rounds',
            'rounds_pct',
            'serials_pct',
            'num_serials',
            'jackpot_pct',
            'plays_pct',
            'bingo_patterns'
        )->where('room', $roomId)->firstOrFail();

        $bingoCreator = true;

        $creatorUserID = BingoRoom::getValueById($roomId, 'creator_user_id');
        $userRole = User::getValueById($creatorUserID, 'user_type');

        $creatorType = RoleRepository::getValueById($userRole, 'prefix');

        if ($creatorType === 'commerce_id') {
            $bingoCreator = BingoCreator::existsByRoomCreatorUser($roomId, $creatorUserID);

            if ($room->room_type === 'PB') {
                $userData = $this->getUserData()['userId'];
                if ($creatorUserID != $userData) {
                    $bingoCreator = BingoCreator::getByRoomCreatorUser($roomId, $userData, ['creator_commn', 'card_price']);
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

        $allowedPrefixes = ['admin', 'commerce_id', 'customer'];

        if (!in_array($userData['type'], $allowedPrefixes)) {
            return false;
        }

        return BingoRoom::with([
            'Creator' => function ($query) use ($userData) {
                $query->select('room', 'status')
                    ->where('creator_user_id', $userData['userId']);
            },
            'Config:room,date,time,card_price'
        ])->select('id', 'encrypted', 'room_commn', 'prize_pool')
            ->where('creator_user_id', '<>', $userData['userId'])
            ->where('room_type', 'PB')->where('status', 2)->get();
    }

    public function participateInPublicRoom($roomId = '', $getOut = false)
    {
        $userData = $this->getUserData();

        $allowedPrefixes = ['admin', 'commerce_id', 'customer'];

        if (!in_array($userData['type'], $allowedPrefixes)) {
            return false;
        }

        DB::beginTransaction();

        try {

            $creatorUser = $userData['userId'];

            if ($getOut) {
                $roomId = decrypt($roomId);

                BingoCreator::where('room', $roomId)
                    ->where('creator_user_id', $creatorUser)
                    ->update(['status' => 0]);
            } else {
                $bingo = BingoRoom::with('Config:id,room,card_price,room_commn')
                    ->where('encrypted', $roomId)->first();

                $bingoCreator = BingoCreator::select('id', 'status')
                    ->where('room', $bingo->id)
                    ->where('creator_user_id', $creatorUser)->first();

                if ($bingoCreator) {
                    $bingoCreator->update(['status' => 1]);
                } else {
                    BingoCreator::create([
                        'room' => $bingo->id,
                        'creator_user_id' => $creatorUser,
                        'creator_type' => Role::getValueByPrefix($userData['type']),
                        'creator_id' => $userData['id'],
                        'creator_commn' => $bingo->Config->room_commn,
                        'card_price' => $bingo->Config->card_price,
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

    public function getInfoPublicRoomPendingPayments()
    {
        $userData = $this->getUserData();

        // Validar el tipo de usuario autorizado
        if (!in_array($userData['type'], ['admin', 'commerce_id', 'customer'])) {
            return false;
        }

        $creatorUser = $userData['userId'];

        // Obtener IDs de salas públicas (PB) con estado 4
        $roomIds = BingoPrizeCtns::where('creator_user_id', $creatorUser)
            ->whereHas('Room', function ($query) {
                $query->where('room_type', 'PB')
                    ->where('status', 4);
            })
            ->where('status', 'WT')->distinct()->pluck('room');

        if ($roomIds->isEmpty()) {
            return collect(); // Retorna colección vacía
        }

        // Obtener ventas del creador para esas salas
        return BingoCreatorSale::with('Currency:id,iso_code')
            ->where('creator_user_id', $creatorUser)->whereIn('room', $roomIds)
            ->get(['currency', 'prize_pool_amount']);
    }

    public function getInfoBingoPlay($id)
    {
        $id = decrypt($id);

        $bingoPlay = BingoPatterns::where('id', $id)->first();

        $boxes = explode(';', $bingoPlay->boxes);

        return response()->json(['name' => $bingoPlay->name, 'boxes' => $boxes]);
    }

    public function bingoPatterns($userToken = false)
    {
        if ($userToken) {
            $userRole = 'customer';
            $userToken = true;
            $user = Customer::getValueByToken($userToken, 'user');
        } else {
            $user = Auth::id();
            $userRoleID = User::getValueById($user, 'user_type');
            $userRole = RoleRepository::getValueById($userRoleID, 'prefix');
        }

        if (!in_array($userRole, ['admin', 'commerce_id', 'customer'])) {
            return redirect()->back();
        }

        $bingoPatterns = BingoPatterns::where('user', $user)->where('status', '<>', 'DELETED')->get();

        $viewData = [
            'bladeLayout' => !$userToken ? 'layouts.auth-layout' : 'layouts.guest-layout',
            'title' => 'Bingo Configuración',
            'name' => 'bingo-patterns',
            'userToken' => $userToken,
            'bingoPatterns' => $bingoPatterns
        ];

        $viewData = ['route' => 'modules.game.bingo.configuration.handle-patterns', 'data' => $viewData];

        return $this->handleViewData($viewData, $userRole);
    }

    public function getInfoByEncrypt($id, $creatorUser, $currency)
    {
        $room = BingoRoom::getByEncrypted($id, ['id', 'prize_pool']);

        if (!$room) return response()->json(['Error' => 'Sala no encontrada']);

        $roomConfig = BingoRoomConfig::select(
            'game_mode',
            'unix_date',
            'card_price',
            'room_commn',
            'prize_pool_type',
            'amount_added_prize_pool',
            'time_chip',
            'rounds',
            'rounds_pct',
            'serials_pct',
            'num_serials',
            'jackpot_pct'
        )->where('room', $room->id)->firstOrFail();

        $currency = decrypt($currency);
        $exchangeRate = 1;

        $creatorUser = decrypt($creatorUser);
        $creatorType = User::getUserTypePrefix($creatorUser);

        if ($creatorType === 'customer') {
            $isLogged = false;
            $custCurrency = CustomerExchangeRate::getById($currency, ['currency', 'exchange_rate']);
            $currency = $custCurrency->currency;
            $exchangeRate = $custCurrency->exchange_rate;
        } else {
            $isLogged = ($currency == 1) ? $this->wallet()->usdt : $this->wallet()->balance;

            if ($currency != 1) {
                $commerce = Commerce::getValueByUserId($creatorUser, 'commerce_id');
                $exchangeRate = CommerceExchangeRate::getCommerceCurrencyValue($commerce, $currency);
            }

            $currency = Currency::getValue($currency, 'symbol');
        }

        $ctnPrice = $roomConfig->card_price * $exchangeRate;

        $bingoCtnPrice = BingoCardPrice::getValueCtnPrice($room->id, $currency, $creatorUser);

        if ($bingoCtnPrice) {
            $ctnPrice = $bingoCtnPrice;
        }

        $room = [
            'dateTime' => $roomConfig->unix_date,
            'currency' => $currency,
            'prizePool' => number_format(($room->prize_pool * $exchangeRate), 2, '.'),
            'prizePoolType' => $roomConfig->prize_pool_type == 1 ? 'Bote' : 'Premio',
            'ctnPrice' => number_format(($ctnPrice), 2, '.')
        ];

        return response()->json(['room' => $room, 'isLogged' => $isLogged]);
    }

    public function copy($id)
    {
        $roomId = decrypt($id);

        $existingRoom = BingoRoom::findOrFail($roomId);
        $existingRoomConfig = BingoRoomConfig::where('room', $roomId)->firstOrFail();
        $existingRoomCreator = BingoCreator::getByRoom($roomId);
        $existingRoomRounds = BingoRound::getByRoom($roomId);
        $existingRoomCtnPrice = BingoCardPriceDraft::getByRoom($roomId, ['currencies', 'card_prices']);

        date_default_timezone_set(config('app.timezone'));

        $userId = $existingRoom->creator_user;
        $boat = $existingRoomConfig->prize_pool_type > 1 ? $existingRoomConfig->amount_added_prize_pool : 0;

        $model = 'BingoRoom';

        $createData = [
            'encrypted' => $this->generateUniqueCode($model),
            'status' => 1,
            'room_type' => $existingRoom->room_type,
            'creator_user_id' => $userId,
            'prize_pool' => $boat,
            'uuid_code' => $this->getCodeUUID($model)
        ];

        DB::beginTransaction();

        try {

            $bingoRKECommission = GameConfig::getGameParamValue('BINGO', 'COMMISSION') ?? 5;

            // Obtener la nueva sala
            $newRoomID = BingoRoom::create($createData)->id;

            BingoRoomConfig::create([
                'room' => $newRoomID,
                'creation' => 'A',
                'creator_user_id' => $userId,
                'game_mode' => $existingRoomConfig->game_mode,
                'date' => $existingRoomConfig->date,
                'time' => $existingRoomConfig->time,
                'unix_date' => strtotime("{$existingRoomConfig->date} {$existingRoomConfig->time}"),
                'card_price' => $existingRoomConfig->card_price,
                'allowed_currencies' => $existingRoomConfig->allowed_currencies,
                'rke_commn' => $bingoRKECommission,
                'room_commn' => $existingRoomConfig->room_commn,
                'prize_pool_type' => $existingRoomConfig->prize_pool_type,
                'amount_added_prize_pool' => $existingRoomConfig->amount_added_prize_pool,
                'time_chip' => $existingRoomConfig->time_chip,
                'rounds' => $existingRoomConfig->rounds,
                'rounds_pct' => $existingRoomConfig->rounds_pct,
                'serials_pct' => $existingRoomConfig->serials_pct,
                'num_serials' => $existingRoomConfig->num_serials,
                'jackpot_pct' => $existingRoomConfig->jackpot_pct,
                'plays_pct' => $existingRoomConfig->plays_pct,
                'bingo_patterns' => $existingRoomConfig->bingo_patterns
            ]);

            // Registar el creador de la sala
            BingoCreator::create([
                'room' => $newRoomID,
                'creator_user_id' => $userId,
                'creator_type' => $existingRoomCreator->creator_type,
                'creator_id' => $existingRoomCreator->creator_id,
                'creator_commn' => $existingRoomConfig->room_commn,
                'card_price' => $existingRoomConfig->card_price,
                'allowed_currencies' => $existingRoomConfig->allowed_currencies,
            ]);

            $roundsPrizeType = $existingRoomRounds->prize_type;
            $roundsPlayType = $existingRoomRounds->bingo_patterns;
            $roundsPrizeValue = $existingRoomRounds->prize_value;

            $this->handleRoundsDrafts('M', 'CREATE', $roundsPrizeType, $roundsPlayType, $roundsPrizeValue, $roomId);

            if ($existingRoomCtnPrice) {
                BingoCardPriceDraft::create([
                    'room' => $roomId,
                    'creator_user_id' => $userId,
                    'currencies' => $existingRoomCtnPrice->currencies,
                    'card_prices' => $existingRoomCtnPrice->card_prices
                ]);
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

        // if ($unixDate <= time()) {
        //     return redirect()->back()->with('message', 'Fecha/Hora no perimitidas')->with('type', 'warning');
        // }

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

        if (BingoRoom::getValueById($id, 'full_house_prize') > 0) {
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
        BingoRoom::findOrFail(decrypt($id))->update(['status' => 1]);

        return redirect()->back()
            ->with('message', 'Sala actualizada')
            ->with('type', 'success');
    }

    public function refund($id) // Reembolsar compras de la sala
    {
        $roomId = decrypt($id);

        if (!BingoRoomUser::where('room', $roomId)->exists()) {
            return true;
        }

        $gameId = Game::getValueByName('BINGO');

        foreach (BingoRoomUser::where('room', $roomId)->cursor() as $player) {

            DB::beginTransaction(); // Iniciar transacción por cada iteración

            try {
                $user = $player->user;

                $amountToReturn = BingoCtnsLot::where('room', $roomId)
                    ->where('player_user', $user)->sum('sale_amount');

                // Obtener la cartera del usuario
                $wallet = Wallet::where('user', $user)->first();

                if (!$wallet) {
                    throw new \Exception("Cartera no encontrada para el usuario ID: $user");
                }

                if ($player->currency === 'USDT') {
                    $wallet->usdt += $amountToReturn;
                } else {
                    $wallet->balance += $amountToReturn;
                }

                // Actualizar cartera
                $wallet->save();

                // Insertar historial de jugadas
                PlayHistory::create([
                    'game' => $gameId,
                    'room' => $roomId,
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
        BingoRoom::where('id', $roomId)->update(['status' => 5]);

        return redirect()->back()->with('message', 'El bote fue reembolsado')->with('type', 'warning');
    }

    public function actWn(Request $request)
    {
        $playerLink = $request->playerLink;
        $randomChip = $request->random_chip_count ?? 4;

        if (ExternalPlayers::where('link', $playerLink)->exists()) {

            $player = ExternalPlayers::getByLink($playerLink, ['room', 'room_user']);

            $roomUser = ['roomUser' => $player->room_user, 'randomChip' => $randomChip];

            $this->handleDraw($player->room, $roomUser);

            return redirect()->back()->with('message', 'Carton Ganador Generado');
        } else {
            return redirect()->back()->with('message', 'El ExternalPlayers no existe');
        }
    }

    public function getAutomaticRoomsInfo($creator)
    {
        //  Lista (CRUD) de configuracion de Salas Automaticas
        app(HandleBingoRoomsService::class)->checkRoomClosure();

        $creatorType = $creator['type'];
        $creatorId = decrypt($creator['id']);

        switch ($creatorType) {
            case 'admin': // Admin
                break;
            case 'commerce_id': // Comercio
                $isLogged = true;

                $commerce = Commerce::getById($creatorId, ['user', 'currency', 'uuid_code']);

                $gameRoomRoute =  route('bingo.gameRoomsCommerce', ['roomType' => 'private', 'currency' => encrypt($commerce->currency)]);

                if ($creatorId == 1) $roomType = Room::getRoomTypes();

                break;
            case 'customer': // Cliente

                $isLogged = $returnBtn = false;

                $customer = Customer::getById($creatorId, ['user', 'customer_id', 'token']);
                $token = $customer->token;

                $gameRoomRoute =  route(
                    'bingo.game-rooms-customer',
                    ['customer' => $customer->customer_id, 'user' => 'available-rooms']
                );

                $moreViewData = ['customer' => true, 'userToken' => $token];

                break;

            default:
                return false;
                break;
        }

        $automaticRooms = BingoRoomAutomatic::where('creator_user_id', $creator['userId'])
            ->whereNot('status', 3)
            ->get([
                'id',
                'time',
                'game_mode',
                'room_type',
                'prize_pool_type',
                'amount_added_prize_pool',
                'card_price',
                'time_chip',
                'rounds',
                'rounds_pct',
                'room_commn',
                'jackpot_pct',
                'serials_pct',
                'num_serials',
                'status'
            ]);

        $activeRooms = BingoRoom::where('creator_user_id', $creator['userId'])->whereIn('status', [2, 3])->exists();

        $timeZoneID = User::getValueById($creator['userId'], 'time_zone');
        $timeZone = TimeZone::getById($timeZoneID, ['name', 'utc']);

        $viewData = [
            'bladeLayout' => $isLogged ? 'layouts.auth-layout' : 'layouts.guest-layout',
            'title' => 'Salas de Bingo',
            'name' => 'bingo-automatic-room',
            'returnBtn' => $returnBtn ?? true,
            'creator' => $creator,
            'timeZone' => $timeZone,
            'rooms' => $automaticRooms,
            'activeRooms' => $activeRooms,
            'gameRoomRoute' => $gameRoomRoute,
            'roomType' => $roomType ?? [],
            'typesPrizePool' => PrizePoolType::all(),
            'gameModes' => GameModes::all()
        ];

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.game.bingo.automatic-room.index', 'data' => $viewData];
    }

    private function allAutomaticRooms($creatorType, $creatorId)
    {
        $creatorId = decrypt($creatorId);

        switch ($creatorType) {
            case 'commerce_id':
                $creator = Commerce::getById($creatorId, ['id', 'user']);
                // Verificar si el comercio existe
                if (!$creator) {
                    return response()->json(['message' => 'El Comercio no existe'], 404);
                }
                break;

            case 'customer':
                $creator = Customer::getByToken($creatorId, ['id', 'user']);
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

    private function getOtherPublicRoomsInfo($currency)
    {
        app(HandleBingoRoomsService::class)->checkRoomClosure();

        $userData = $this->getUserData();

        switch ($userData['type']) {
            case 'admin': // Admin
                break;
            case 'commerce_id': // Comercio
                $isLogged = true;

                $exchangeRate = CommerceExchangeRate::getCommerceCurrencyValue($userData['id'], $currency);

                $moreViewData = [
                    'uuidCode' => UniqueCode::getValueByUser($userData['userId'], 'uuid_code')
                ];
                break;
            case 'customer':
                return false;
                break;

            default:
                return false;
                break;
        }

        $currencyData = Currency::getById($currency, ['symbol', 'name', 'iso_code']);

        $currencies = Currency::getCurrenciesById([...CommerceExchangeRate::getCurrenciesByCommerce($userData['id']), 1]);

        $rooms = $this->getInfoRooms($userData['userId'], 'public-rooms', $currency, true);

        $creator = ['type' => $userData['type'], 'id' => encrypt($userData['id'])];

        $routeWithCurrency = [
            'route' => 'bingo.room-summary',
            'parameters' => [
                'roomType' => 'other-public-rooms',
                'creatorType' => $userData['type'],
                'creatorId' => encrypt($userData['id'])
            ]
        ];

        $viewData = [
            'bladeLayout' => $isLogged ? 'layouts.auth-layout' : 'layouts.guest-layout',
            'title' => 'Salas de Bingo',
            'subtitle' => 'Otras Salas Publicas',
            'name' => 'bingo',
            'returnBtn' => $returnBtn ?? true,
            'creator' => $creator,
            'currency' => $currency,
            'currencyData' => $currencyData,
            'currencies' => $currencies,
            'exchangeRate' => $exchangeRate ?? 1,
            'rooms' => $rooms,
            'routeWithCurrency' => $routeWithCurrency
        ];

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.game.bingo.form.other-public-rooms', 'data' => $viewData];
    }

    private function allOtherPublicRooms($creatorType, $creatorId, $currency = '')
    {
        if (!$currency) {
            return redirect()->route('bingo.room-summary', [
                'roomType' => 'other-public-rooms',
                'creatorType' => $creatorType,
                'creatorId' => $creatorId,
                'currency' => encrypt(1)
            ]);
        }

        $creatorId = decrypt($creatorId);

        switch ($creatorType) {
            case 'commerce_id':
                $creator = Commerce::getById($creatorId, ['id', 'user']);
                // Verificar si el comercio existe
                if (!$creator) {
                    return response()->json(['message' => 'El Comercio no existe'], 404);
                }
                break;

            case 'customer':
                $creator = Customer::getByToken($creatorId, ['id', 'user']);
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
        $viewData = $this->getOtherPublicRoomsInfo(decrypt($currency));

        // Retornar la vista con la información
        return $this->handleViewData($viewData, $creatorType);
    }

    private function handleCreateAutomaticRoom($data, $replay = false)
    {
        DB::beginTransaction();

        try {

            $bingoRKECommission = GameConfig::getGameParamValue('BINGO', 'COMMISSION') ?? 5;

            $creator = json_decode($data->creator);
            $creatorUser = Auth::id();

            $roundsPct = $data->rounds_pct ?? null;
            $ctnPriceCurrencies = $data->card_price_currencies;

            $automaticBingoId = BingoRoomAutomatic::create([
                'time' => $data->time,
                'game_mode' => $data->game_mode,
                'room_type' => $data->room_type,
                'prize_pool_type' => $data->prize_pool_type,
                'amount_added_prize_pool' => $data->amount_added_prize_pool,
                'time_chip' => $data->time_chip,
                'rounds' => $data->rounds,
                'rounds_pct' => $roundsPct,
                'card_price' => $data->card_price,
                'allowed_currencies' => $data->allowed_currencies,
                'rke_commn' => $bingoRKECommission,
                'room_commn' => $data->room_commn,
                'jackpot_pct' => $data->serials_commission,
                'plays_pct' => null,
                'bingo_patterns' => null,
                'serials_pct' => $data->serials_pct,
                'num_serials' => $data->num_serials,
                'creator_type' => Role::getValueByPrefix($creator->type, 'id'),
                'creator_id' => decrypt($creator->id),
                'creator_user_id' => $creatorUser
            ])->id;

            $roundsPrizeType = $data->rounds_prize_type ?? [];
            $roundsPlayType = $request->rounds_bingo_patterns ?? [];
            $roundsPrizeValue = $data->rounds_prize_value ?? [];

            $this->handleRoundsDrafts('A', 'CREATE', $roundsPrizeType, $roundsPlayType, $roundsPrizeValue, $automaticBingoId);

            if ($ctnPriceCurrencies) {
                $this->handleCtnPriceCurrencies('A', 'CREATE', $ctnPriceCurrencies, $automaticBingoId, $creatorUser);
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
        $automaticBingoId = decrypt($id);

        DB::beginTransaction();

        try {

            $bingoRKECommission = GameConfig::getGameParamValue('BINGO', 'COMMISSION') ?? 5;

            $ctnPriceCurrencies = $request->card_price_currencies;

            BingoRoomAutomatic::where('id', $id)->update([
                'time' => $request->time,
                'game_mode' => $request->game_mode,
                'room_type' => $request->room_type,
                'prize_pool_type' => $request->prize_pool_type,
                'amount_added_prize_pool' => $request->amount_added_prize_pool,
                'time_chip' => $request->time_chip,
                'rounds' => $request->rounds,
                'rounds_pct' => $request->rounds_pct,
                'card_price' => $request->card_price,
                'allowed_currencies' => $request->allowed_currencies,
                'rke_commn' => $bingoRKECommission,
                'room_commn' => $request->room_commn,
                'serials_pct' => $request->serials_pct,
                'num_serials' => $request->num_serials
            ]);

            $roundsPrizeType = $request->rounds_prize_type ?? [];
            $roundsPlayType = $request->rounds_bingo_patterns ?? [];
            $roundsPrizeValue = $request->rounds_prize_value ?? [];

            $this->handleRoundsDrafts('A', 'UPDATE', $roundsPrizeType, $roundsPlayType, $roundsPrizeValue, $automaticBingoId);

            if ($ctnPriceCurrencies) {
                $this->handleCtnPriceCurrencies('A', 'UPDATE', $ctnPriceCurrencies, $automaticBingoId);
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
            'prize_pool_type',
            'amount_added_prize_pool',
            'room_commn',
            'card_price',
            'time_chip',
            'rounds',
            'rounds_pct',
            'serials_pct',
            'num_serials',
            'jackpot_pct',
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

    public function executeGenerateBingoRoomsJob($creatorType, $creatorId)
    {
        $creatorId = decrypt($creatorId);

        switch ($creatorType) {
            case 'admin':
                //$existsCreator = Admin::existsValue('id', $creatorId);
                break;
            case 'commerce_id':
                $existsCreator = Commerce::existsValue('id', $creatorId);
                break;
            case 'customer':
                $existsCreator = Customer::existsValue('id', $creatorId);
                break;
            default:
                $existsCreator = false;
                break;
        }

        if (!$existsCreator) {
            return redirect()->back()->with('message', 'El creador no existe')->with('type', 'warning');
        }

        $creatorType = Role::getValueByPrefix($creatorType, 'id');

        $creator = ['type' => $creatorType, 'id' => $creatorId];

        $service = new AutoGenerateBingoRoomsService();
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
                $success = BingoRoomUser::where('id', $prizeCtn->room_user)->update(['winner' => '2']);
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
    private function getRoomsData($user, $currency, $dateFilter = "today")
    {
        switch ($user['type']) {

            case 'commerce_id':

                $creatorUser = $user['userId'];

                $query = BingoRoom::with([
                    'Config:room,date,time,card_price,rke_commn,room_commn',
                    'Status',
                    'CtnsLot' => function ($subquery) use ($creatorUser, $currency) {
                        $subquery->select('room', 'currency', 'ctns', 'sale_amount', 'seller_commn')
                            ->where('creator_user_id', $creatorUser)
                            ->where('currency', $currency)->with('Currency');
                    }
                ])->whereHas('CtnsLot', function ($query) use ($creatorUser, $currency) {
                    $query->where('creator_user_id', $creatorUser)->where('currency', $currency);
                });

                break;
            case 'seller':

                $seller = $this->seller(['id']);
                $sellerId = $seller->id;

                $query = BingoRoom::with([
                    'Config:room,time,date,card_price',
                    'CtnsLot' => function ($query) use ($sellerId, $currency) {
                        $query->select('room', 'currency', 'ctns', 'sale_amount', 'seller_commn')
                            ->where('seller', $sellerId)->where('currency', $currency)->with('Currency');
                    }
                ])->whereHas('CtnsLot', function ($query) use ($sellerId, $currency) {
                    $query->where('seller', $sellerId)->where('currency', $currency);
                });

                break;
            default:
                # code...
                break;
        }

        $query = $this->applyDateFilter($dateFilter, $query, 'publication_date');

        $rooms = $query->get(['id', 'encrypted', 'room_type', 'status', 'prize_pool']);

        return $rooms;
    }

    public function reportSalesRooms($currency = '', $dateFilter = 'today')
    {
        $userData = $this->getUserData();
        $verifyCurrency = $this->verifyExistenceOfSales($userData, $currency, $dateFilter);

        if (isset($verifyCurrency['return'])) {
            return redirect()->route('bingo.sales-report', [
                'currency' => $verifyCurrency['currency'],
                'dateFilter' => 'all'
            ]);
        }

        $title = 'Reporte de Ventas';
        $routeWithCurrency = [
            'route' => 'bingo.sales-report',
            'parameters' => ['currency' => $currency, 'dateFilter' => $dateFilter]
        ];

        $currency = $verifyCurrency;
        $userRole = $userData['type'];
        $rooms = $this->getRoomsData($userData, $currency, $dateFilter);
        $currencies = $this->getUserCurrencies($userData, ['CtnsLot'], $dateFilter);

        $currencyData = Currency::getById($currency, ['symbol', 'name', 'iso_code']);

        $totals = collect([
            'ctns' => 0,
            'sales' => 0,
            'roomCommn' => 0,
            'sellerCommn' => 0,
            'prizePool' => 0,
        ]);

        $roomsData = [];

        foreach ($rooms as $room) {
            $ctns = $room->CtnsLot->sum('ctns');
            $allSale = $room->CtnsLot->sum('sale_amount');
            $sellerCommn = $room->CtnsLot->sum('seller_commn');

            $row = [
                'encrypted' => $room->encrypted,
                'roomType' => $room->room_type_description,
                'date' => Carbon::parse($room->Config->date)->format('d/m/y'),
                'time' => $room->Config->time,
                'ctns' => $ctns,
                'sales' => number_format($allSale, 2, '.', ','),
                'sellerCommn' => number_format($sellerCommn, 2, '.', ','),
            ];

            $totals['ctns'] += $ctns;
            $totals['sales'] += $allSale;
            $totals['sellerCommn'] += $sellerCommn;

            if ($userRole === 'commerce_id') {
                $roomCommn = $allSale * ($room->Config->room_commn / 100);
                $rkeCommn = $allSale * ($room->Config->rke_commn / 100);
                $prizePool = $allSale - $roomCommn - $sellerCommn - $rkeCommn;

                $row['roomCommn'] = number_format($roomCommn, 2, '.', ',');
                $row['sellerCommn'] = number_format($sellerCommn, 2, '.', ',');
                $row['prizePool'] = number_format($prizePool, 2, '.', ',');

                $totals['roomCommn'] += $roomCommn;
                $totals['sellerCommn'] += $sellerCommn;
                $totals['prizePool'] += $prizePool;
            }

            $roomsData[] = $row;
        }

        // Formatear totales
        $totalsFormatted = [
            'ctns' => $totals['ctns'],
            'sales' => number_format($totals['sales'], 2, '.', ','),
            'sellerCommn' => number_format($totals['sellerCommn'], 2, '.', ','),
        ];

        if ($userRole === 'commerce_id') {
            $totalsFormatted['roomCommn'] = number_format($totals['roomCommn'], 2, '.', ',');
            $totalsFormatted['sellerCommn'] = number_format($totals['sellerCommn'], 2, '.', ',');
            $totalsFormatted['prizePool'] = number_format($totals['prizePool'], 2, '.', ',');
            $view = 'modules.commerce.games.bingo.report.rooms';
        } else {
            $view = 'modules.seller.games.bingo.report.rooms';
        }

        $params = compact(
            'title',
            'routeWithCurrency',
            'dateFilter',
            'currencies',
            'currency',
            'currencyData',
            'roomsData',
            'totalsFormatted'
        );

        if (isset($otherParams)) {
            $params = array_merge($params, $otherParams);
        }

        return view($view, $params);
    }

    public function reportSalesByRoom($roomEncrypted, $currency)
    {
        $userData = $this->getUserData();

        $routeWithCurrency = [
            'route' => 'bingo.reportSalesByRoom',
            'parameters' => ['room' => $roomEncrypted, 'currency' => $currency]
        ];

        $room = BingoRoom::getByEncrypted($roomEncrypted, ['id', 'encrypted', 'creator_user_id']);
        $roomId = $room->id;

        $verifyCurrency = $this->verifyExistenceOfSales($userData, $currency, false, $roomId);

        if (isset($verifyCurrency['return'])) {
            return redirect()->route(
                'bingo.reportSalesByRoom',
                ['room' => $roomEncrypted, 'currency' => $verifyCurrency['currency']]
            );
        }

        $title = 'Reporte de Sala';
        $cardTitle = "<span> Reporte de Sala </span> <span class='badge badge-secondary'>{$room->encrypted}</span>";

        $currency = $verifyCurrency;
        $currencyData = Currency::getById($currency, ['symbol', 'name', 'iso_code']);

        $where = collect([
            (object)[
                'relation' => 'CtnsLot',
                'constraints' => function ($query) use ($roomId, $userData) {
                    $query->where('room', $roomId);

                    if ($userData['type'] !== 'commerce_id') {
                        $query->where('seller', $userData['id']);
                    }
                }
            ]
        ]);

        $currencies = $this->getUserCurrencies($userData, ['CtnsLot'], 'all', $where);

        $totals = collect([
            'ctns' => 0,
            'sales' => 0,
            'roomCommn' => 0,
            'sellerCommn' => 0,
            'prizePool' => 0,
        ]);

        switch ($userData['type']) {

            case 'commerce_id':

                $ctnsLotFilter = fn($query) => $query->where('room', $roomId)->where('currency', $currency);

                $sellers = Seller::select('id', 'name', 'country')
                    ->with([
                        'Country:id,name',
                        'CtnsLot' => $ctnsLotFilter,
                    ])
                    ->where('commerce_id', $userData['id'])
                    ->whereHas('CtnsLot', $ctnsLotFilter)
                    ->get();

                $bingoConfig = BingoRoomConfig::getByRoom($roomId, ['rke_commn', 'room_commn']);

                $view = 'modules/commerce/games/bingo/report/by-room';

                $otherParams = ['sellers' => $sellers, 'bingoConfig' => $bingoConfig];

                break;

            case 'seller':

                $sellerId = $userData['id'];

                $playersRoom = ExternalPlayers::with([
                    'CtnsLot' => function ($query) use ($sellerId, $currency) {
                        $query->select('room', 'room_user', 'currency', 'ctns', 'sale_amount', 'seller_commn')
                            ->where('seller', $sellerId)->where('currency', $currency);
                    }
                ])->where('seller', $sellerId)->where('room', $roomId)->where('currency', $currency)
                    ->get(['room', 'name', 'lastname', 'phone', 'link', 'currency', 'room_user']);

                if (!count($playersRoom)) return redirect()->back();

                $ctnsLots = $playersRoom->flatMap->CtnsLot;

                $totals['ctns'] = $ctnsLots->sum('ctns');
                $totals['sales'] = $ctnsLots->sum('sale_amount');

                $otherParams = ['playersRoom' => $playersRoom];

                $view = 'modules.seller.games.bingo.report.by-room';

                break;
            default:
                # code...
                break;
        }

        // Formatear totales
        $totalsFormatted = [
            'ctns' => $totals['ctns'],
            'sales' => number_format($totals['sales'], 2, '.', ',')
        ];

        $params = compact(
            'title',
            'cardTitle',
            'routeWithCurrency',
            'currency',
            'currencyData',
            'currencies',
            'totalsFormatted'
        );

        if (isset($otherParams)) {
            $params = array_merge($params, $otherParams);
        }

        return view($view, $params);
    }

    public function ctnsSold($currency = '', $dateFilter = 'today')
    {
        $userData = $this->getUserData();

        $verifyCurrency = $this->verifyExistenceOfSales($userData, $currency, $dateFilter);

        if (isset($verifyCurrency['return'])) {
            return redirect()->route(
                'bingo.cartons-sold',
                ['currency' => $verifyCurrency['currency'], 'dateFilter' => 'all']
            );
        }

        $title = 'Cartones Vendidos';
        $moduleId = 'notify-payment-ctns-off';

        $routeWithCurrency = [
            'route' => 'bingo.cartons-sold',
            'parameters' => ['currency' => $currency, 'dateFilter' => $dateFilter]
        ];

        $currency = $verifyCurrency;
        $currencyData = Currency::getById($currency, ['symbol', 'name', 'iso_code']);
        $currencies = $this->getUserCurrencies($userData, ['CtnsLot'], $dateFilter);

        switch ($userData['type']) {

            case 'commerce_id':

                $ctnsSoldQuery = BingoCtnsLot::with([
                    'Room:id,encrypted,room_type,status',
                    'Payment:id,lot,payment_proof',
                    'Seller:id,name'
                ])->whereHas('Room', function ($query) {
                    $query->whereIn('status', [2, 3, 4]);
                });

                $ctnsSoldQuery = $this->applyDateFilter($dateFilter, $ctnsSoldQuery);

                $ctnsSold = $ctnsSoldQuery->where('currency', $currency)
                    ->where('creator_user_id', $userData['userId'])
                    ->whereHas('Seller')->orderBy('status')->orderByDesc('id')->get();

                $view = 'modules.commerce.games.bingo.cartons.ctns-sold';

                break;

            case 'seller':

                $sellerId = $userData['id'];
                $commerce = Seller::getValueById($sellerId, 'commerce_id');

                $ctnsSoldQuery = BingoCtnsLot::with([
                    'Room:id,encrypted,room_type,status',
                    'Payment:id,lot,payment_proof',
                    'ExternalPlayers:id,room_user,name,lastname,country',
                    'ExternalPlayers.Country:id,name'
                ])->whereHas('Room', function ($query) {
                    $query->whereIn('status', [2, 3, 4]);
                });

                $ctnsSoldQuery = $this->applyDateFilter($dateFilter, $ctnsSoldQuery);

                $ctnsSold = $ctnsSoldQuery->where('currency', $currency)->where('seller', $sellerId)
                    ->orderBy('status')->orderByDesc('id')->get();

                $allPendingCtnSale = $pendingCtns = 0;

                foreach ($ctnsSold as $value) {
                    if ($value->status == 0) {
                        $allPendingCtnSale += $value->sale_amount;
                        $pendingCtns++;
                    }
                }

                $view = 'modules/seller/games/bingo/cartons/ctns-sold';

                $otherParams = [
                    'allPendingCtnSale' => $allPendingCtnSale,
                    'pendingCtns' => $pendingCtns,
                    'commerceName' => Commerce::getValueById($commerce, 'name')
                ];

                break;

            default:
                # code...
                break;
        }

        $params = compact(
            'title',
            'name',
            'routeWithCurrency',
            'dateFilter',
            'ctnsSold',
            'currency',
            'currencyData',
            'currencies'
        );

        if (isset($otherParams)) {
            $params = array_merge($params, $otherParams);
        }

        return view($view, $params);
    }

    public function winners($type = 'private', $dateFilter = "today", $currency = '')
    {
        if (!in_array($type, ['private', 'public'])) {
            abort(404); // Retorna un error 404 Not Found
        }

        $userData = $this->getUserData();

        $verifyCurrency = $this->verifyExistenceOfSales($userData, $currency, $dateFilter);

        if (isset($verifyCurrency['return'])) {
            return redirect()->route(
                'bingo.winners',
                [
                    'type' => $type,
                    'dateFilter' => 'all',
                    'currency' => $verifyCurrency['currency']
                ]
            );
        }

        $routeWithCurrency = [
            'route' => 'bingo.winners',
            'parameters' => [
                'type' => $type,
                'dateFilter' => $dateFilter,
                'currency' => $currency
            ]
        ];

        app(HandleBingoRoomsService::class)->checkRoomClosure();

        $title = 'Ganadores';
        $moduleId = 'bingo-winners';

        $currency = $verifyCurrency;
        $currencyData = Currency::getById($currency, ['symbol', 'name', 'iso_code']);

        switch ($userData['type']) {

            case 'commerce_id':

                $creatorUser = $userData['userId'];

                // Pagos Pendientes de otros comercios
                $paymentsOtherCommerces = 0;

                // Contar los ganadores pendientes de pago para otros comercios
                $countWinnersOtherCommerces = BingoPrizeCtns::getPendingPaymentsCountExcludingCreator($creatorUser);

                // Contar los ganadores pendientes de pago para el propio comercio
                $countWinnersByCommerce = BingoPrizeCtns::getPendingPaymentsCountByCreator($creatorUser);

                $currencies = $this->getUserCurrencies($userData, ['PrizeCtns'], $dateFilter);

                // Obtener los datos de los ganadores
                $winnersQuery = BingoPrizeCtns::with([
                    'Room:id,encrypted,room_type',
                    'Room.Config:id,room,date,time',
                    'Payments:prize_ctn,payment_proof',
                    'ExternalPlayers.Country:id,name',
                    'Seller.Commerce:id,name',
                    'Seller' => function ($query) {
                        $query->select('id', 'name', 'commerce_id');
                    }
                ])->whereHas('Room', function ($query) {
                    $query->where('status', '<', 5)->with('CtnsLot');
                })->whereHas('Seller');

                if ($type === 'private') {
                    $winnersQuery->where('creator_user_id', $creatorUser);
                } else {
                    $winnersQuery->whereNot('creator_user_id', $creatorUser);
                }

                $winnersQuery = $this->applyDateFilter($dateFilter, $winnersQuery);

                $winners = $winnersQuery->where('active', 'Y')->orderBy('prize_type')->get();

                if ($type === 'private') {
                    $view = 'modules.commerce.games.bingo.cartons.awarded.my-winners';
                } else {
                    $view = 'modules.commerce.games.bingo.cartons.awarded.other-winners';
                }

                $otherParams = [
                    'countWinnersOtherCommerces' => $countWinnersOtherCommerces,
                    'countWinnersByCommerce' => $countWinnersByCommerce
                ];

                break;

            case 'seller':

                $winnersQuery = BingoPrizeCtns::with([
                    'Payments:prize_ctn,payment_proof',
                    'Room:id,encrypted,room_type',
                    'ExternalPlayers.Country:id,name',
                ])->whereHas('Room', function ($query) {
                    $query->where('status', 4);
                });

                $winnersQuery = $this->applyDateFilter($dateFilter, $winnersQuery);

                $winners = $winnersQuery->select(
                    'room',
                    'round',
                    'seller',
                    'room_user',
                    'ctn',
                    'prize_type',
                    'currency',
                    'amount',
                    'status'
                )->where('seller', $userData['id'])
                    ->where('active', 'Y')->where('currency', $currency)->get();

                $currencies = $this->getUserCurrencies($userData, ['PrizeCtns'], $dateFilter);

                $view = 'modules.seller.games.bingo.cartons.awarded.winners';

                break;

            default:
                break;
        }

        $params = compact(
            'title',
            'name',
            'winners',
            'routeWithCurrency',
            'dateFilter',
            'currency',
            'currencyData',
            'currencies'
        );

        if (isset($otherParams)) {
            $params = array_merge($params, $otherParams);
        }

        return view($view, $params);
    }

    public function reportSalesByRoomWinners($roomEncrypted, $currency)
    {
        $userData = $this->getUserData();

        $room = BingoRoom::getByEncrypted($roomEncrypted, ['id', 'encrypted']);
        $roomId = $room->id;

        $verifyCurrency = $this->verifyExistenceOfSales($userData, $currency, false, $roomId);

        if (isset($verifyCurrency['return'])) {
            return redirect()->route(
                'bingo.reportSalesByRoomWinners',
                [
                    'room' => $roomEncrypted,
                    'currency' => $verifyCurrency['currency']
                ]
            );
        }

        $routeWithCurrency = [
            'route' => 'bingo.reportSalesByRoomWinners',
            'parameters' => [
                'room' => $roomEncrypted,
                'currency' => $currency
            ]
        ];

        app(HandleBingoRoomsService::class)->checkRoomClosure();

        $title = 'Reporte de Sala';
        $moduleId = 'bingo-winners';
        $cardTitle = "<span> Reporte de Sala </span> <span class='badge badge-secondary'>{$room->encrypted}</span>";

        $currency = $verifyCurrency;
        $currencyData = Currency::getById($currency, ['symbol', 'name', 'iso_code']);

        switch ($userData['type']) {

            case 'commerce_id':

                $creatorUser = $userData['userId'];

                $where = collect([
                    (object)[
                        'relation' => 'PrizeCtns',
                        'constraints' => function ($query) use ($roomId, $creatorUser) {
                            $query->where('room', $roomId)->where('creator_user_id', $creatorUser);
                        }
                    ]
                ]);

                $currencies = $this->getUserCurrencies($userData, ['PrizeCtns'], 'all', $where);

                // Obtener los datos de los ganadores
                $winnersQuery = BingoPrizeCtns::with([
                    'Room:id,encrypted,room_type',
                    'Room.Config:id,room,date,time',
                    'ExternalPlayers.Country',
                    'Payments',
                    'Seller.Commerce:id,name',
                    'Seller' => function ($query) {
                        $query->select('id', 'name', 'commerce_id');
                    }
                ])->whereHas('Room', function ($query) {
                    $query->where('status', 4);
                })->whereHas('Seller')->whereHas('CtnsLot')
                    ->whereHas('GameExchangeRate');

                $winners = $winnersQuery->where('active', 'Y')
                    ->where('creator_user_id', $creatorUser)
                    ->where('room', $roomId)->orderBy('prize_type')->get();

                $view = 'modules.commerce.games.bingo.cartons.awarded.by-room';

                break;

            case 'seller':

                $winnersQuery = BingoPrizeCtns::with([
                    'Payments:prize_ctn,payment_proof',
                    'ExternalPlayers.Country:id,name',
                ])->whereHas('Room', function ($query) {
                    $query->whereColumn('id', 'bingo_prize_ctns.room')->where('status', 4);
                });

                $winners = $winnersQuery->where('seller', $userData['id'])
                    ->where('room', $roomId)->where('active', 'Y')
                    ->where('currency', $currency)->get();

                $where = collect([
                    (object)[
                        'relation' => 'PrizeCtns',
                        'constraints' => function ($query) use ($roomId, $userData) {
                            $query->where('room', $roomId)->where('seller', $userData['id']);
                        }
                    ]
                ]);

                $currencies = $this->getUserCurrencies($userData, ['PrizeCtns'], 'all', $where);

                $view = 'modules.seller.games.bingo.cartons.awarded.by-room';

                break;

            default:
                break;
        }

        $params = compact(
            'title',
            'cardTitle',
            'name',
            'winners',
            'routeWithCurrency',
            'currency',
            'currencyData',
            'currencies'
        );

        if (isset($otherParams)) {
            $params = array_merge($params, $otherParams);
        }

        return view($view, $params);
    }

    public function regenerateDraw(Request $request)
    {
        // FUNCION PARA REGENERAR SORTEO - USAR SOLO PARA TESTES

        $roomEncrypted = $request->room;
        $room = BingoRoom::getValueByEncrypted($roomEncrypted, 'id');
        $keyword = $request->keyword;

        if (!$room) {
            return response()->json([
                'type' => 'error',
                'message' => 'Sala no encontrada'
            ]);
        }

        if (BingoDraw::where('room', $room)->doesntExist()) {
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

        // Resetear estados de las rondas
        BingoRound::where('room', $room)->update(['status' => 'ACTIVE']);

        // Obtener símbolos
        $symbols = BingoDrawCode::where('room', $room)
            ->pluck('sym', 'num');

        if ($symbols->isEmpty()) {
            $this->encryptDraw($room);
            $symbols = BingoDrawCode::where('room', $room)->pluck('sym', 'num');
        }

        $roundsCount = BingoRoomConfig::getValueByRoom($room, 'rounds');
        $currentTimestamp = now()->addMinutes(1)->timestamp;

        $chipsUsed = [];
        $updates = [];

        for ($round = 1; $round <= $roundsCount; $round++) {

            $draws = BingoDraw::where('room', $room)->where('round', $round)->get();

            // Resetear ficha ganadora anterior
            BingoDraw::where('room', $room)
                ->where('round', $round)
                ->where('status', 3)
                ->update(['status' => 0]);

            $timeIncrement = 0;

            foreach ($draws as $index => $draw) {
                do {
                    $rnd = mt_rand(1, 75);
                } while (isset($chipsUsed[$rnd]));

                $chipsUsed[$rnd] = true;

                $numEncoded = collect(str_split($rnd))
                    ->map(fn($digit) => $symbols[$digit == '0' ? 10 : $digit] ?? '')
                    ->implode('');

                $updates[] = [
                    'id' => $draw->id,
                    'chip' => $rnd,
                    'code' => $numEncoded,
                    'time' => $currentTimestamp + ($index * $timeIncrement),
                    'round' => $round,
                    'updated_at' => now()
                ];

                $timeIncrement = 5;
            }
        }

        DB::beginTransaction();

        try {
            // Resetear ganadores y eliminar premios
            BingoRoomUser::where('room', $room)->where('winner', '1')->update(['winner' => '0']);
            BingoPrizeCtnsPayment::where('room', $room)->delete();
            BingoPrizeCtns::where('room', $room)->delete();

            // Actualizar sorteos
            foreach ($updates as $update) {
                BingoDraw::where('id', $update['id'])->update([
                    'chip' => $update['chip'],
                    'code' => $update['code'],
                    'time' => $update['time'],
                    'round' => $update['round'],
                    'updated_at' => $update['updated_at'],
                ]);
            }

            // Ejecutar ficha ganadora
            // $this->registerWinningChips($room);

            // Actualizar estado de la sala
            BingoRoom::where('id', $room)->update([
                'status' => 3,
                'paid_win' => 0,
                'full_house_prize' => BingoRoom::getValueById($room, 'prize_pool'),
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
            Log::error("Error al regenerar sorteo [$room]: " . $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'Ha ocurrido un error'
            ]);
        }
    }

    public function showGameInfo()
    {
        $data = [
            'round' => 5,
            'numbers' => [12, 23, 34, 45, 56],
        ];
        return view('modules.game.bingo.game-info', compact('data'));
    }

    public function callNumber(Request $request, int $roomId)
    {
        $number = $request->input('number');
        $round = $request->input('round');

        // Validar el número si es necesario
        if (!$number || !is_numeric($number)) {
            return response()->json(['error' => 'Número inválido.'], 400);
        }

        // Lógica para guardar o validar el número llamado...
        // BingoDraw::where('room', $roomId)->where('round', $round)
        //     ->create([
        //         'chip' => $number,
        //         'code',
        //         'room',
        //         'round',
        //         'time',
        //         'status',
        //     ]);

        // Disparar evento
        event(new BingoNumberCalled($number, $roomId));

        return response()->json(['message' => 'Número llamado enviado al canal']);
    }

    public function handleForm($roomType, $roomId)
    {
        // Validar tipo de sala
        if (!$this->roomTypeService->isValid($roomType)) {
            return redirect()->back();
        }

        // $timeZoneID = UserRepository::getValueById($creatorUser, 'time_zone');
        // $timeZone = TimeZone::getById($timeZoneID, ['name', 'utc']);

        $data = [
            // 'timeZone' => $timeZone,
            'numbers' => [12, 23, 34, 45, 56],
        ];

        return view('modules.game.bingo.form.index', compact('data'));
    }
}
