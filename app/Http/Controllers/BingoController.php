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
use App\Models\BingoCreator;
use App\Models\BingoRoomConfig;
use App\Models\BingoDrawCode;
use App\Models\BingoRoomAutomatic;
use App\Models\Commerce;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\GameModes;
use App\Models\JackpotType;
use App\Models\PlayerLink;
use App\Models\Customer;
use App\Models\CustomerExchangeRate;
use App\Models\CustomerGames;
use App\Models\CustomerUser;
use App\Models\Games;
use App\Models\Room;
use App\Models\Seller;
use App\Models\UserPurchaseCodes;
use App\Models\UserType;
use App\Services\AutoGenerateBingoRooms;
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
use PDO;

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
        // Verificar Cierre de Salas
        $this->checkRoomClosure();

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

                $isCreator = Commerce::getValueByUserId($player, 'id');

                $player = !$isCreator ? false : encrypt($player);

                $moreViewData = ['player' => $player, 'wallet' => $this->wallet()];
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
        ])->where($creator['type'], $creatorID)->whereIn('status', [2, 3])->get();

        // Redirigir si no hay salas disponibles
        if (!count($rooms) && $creator['type'] === 'commerce') {
            return ['redirect' => route(
                'bingo.all-rooms',
                [
                    'creatorType' => 'commerce',
                    'userID' => encrypt($creatorID),
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

    public function gamesRoomsCommerce($coin = '')
    {
        // Vista para todas las salas disponibles o en juego de un comercio 

        // Obtner ID d Comercio a traves del ID de usuario
        $commerce = Commerce::getValueByUserId(Auth::id(), 'id');

        if (!$coin) {
            return redirect()->route(
                'bingo.all-rooms',
                [
                    'creatorType' => 'commerce',
                    'userID' => $commerce,
                    'coin' => encrypt(1)
                ]
            );
        }

        $creator = ['type' => 'commerce', 'id' => $commerce];

        $viewData = self::getGamesRoomsView($creator, ['coin' => decrypt($coin)]);

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

        $roomID = $room->id;
        $creator = $request->user_type;

        if ($creator) {

            $creatorID = decrypt($request->user_type_val);
            $coin = decrypt($request->coin);
            $player = !empty($request->player) ? decrypt($request->player) : null;

            $isLogin = $request->is_login ?? false;
            $data['isLogin'] = $isLogin;
            $data['coin'] = $coin;

            if ($creator === 'customer') {

                $user = CustomerUser::getById($player, ['token', 'coin']);

                $customerResponse = $this->getUserBalanceFromCustomer($creatorID, $user->token);

                if (isset($customerResponse['Error'])) {
                    Log::error('Ha ocurrido un error al generar Carton del Cliente/Jugador: ' . $customerResponse['Error']);
                    return response()->json(['type' => 'warning', 'message' => 'Ha ocurrido un error en la solicitud']);
                }

                $exchangeRate = CustomerExchangeRate::getCustomerCoinValue($creatorID, $user->coin);

                // Monto total a pagar 
                $saleAmount = $room->ctn_price * $exchangeRate * $data['numOfCtns'];

                if ($customerResponse['Data'][0]['Amount'] < $saleAmount) {
                    // Validar saldo disponible
                    return response()->json(['type' => 'warning', 'message' => 'Saldo insuficiente']);
                }

                $data['customer'] = $creatorID;
                $data['playerUser'] = $player ?? null;
                $playerColumn = 'player_user';
            } else {
                $data['commerce'] = $creatorID;


                if ($isLogin == true) {
                    $data['playerUser'] = $player;
                    $playerColumn = 'player_user';

                    // Determinar la columna de saldo en función de la moneda
                    $columnBalance = $coin == 1 ? 'usdt' : 'balance';
                    $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($creatorID, $coin);

                    $saleAmount = $room->ctn_price * $exchangeRate * $data['numOfCtns'];
                    $balance = Wallet::getValue($player, $columnBalance);

                    if ($balance < $saleAmount) {
                        return response()->json(['type' => 'warning', 'message' => 'Saldo insuficiente']);
                    }
                } else {
                    if ($request->purchase_code) {
                        // Verificar si el Codigo de Compra es Valido
                        $purchaseCode = UserPurchaseCodes::existsPurchaseCode($request->purchase_code);

                        if (!$purchaseCode) {
                            return response()->json(['type' => 'warning', 'message' => 'Codigo de Compra no existe']);
                        }
                    }

                    $data['playerLink'] =  $player ?? null;
                    $playerColumn = 'player_link';

                    if ($request->user_in_game) {
                        $data['country'] = $request->country ?? null;
                        $data['playerPhone'] = $request->phone ?? null;
                        $data['playerName'] = $request->name ?? null;
                        $data['playerLastname'] = $request->last_name ?? null;
                    }
                }
            }
        } else {
            $seller = Seller::getByUserId(Auth::id(), ['id', 'commerce']);
            $country = $request->country;

            $data['commerce'] = $seller->commerce;
            $data['seller'] = $seller->id;
            $data['country'] = $country;
            $data['coin'] = Coin::getValueByCountry($country, 'id');
            $data['playerPhone'] = $request->phone ?? null;
            $data['playerName'] = $request->name ?? null;
            $data['playerLastname'] = $request->last_name ?? null;
        }

        $cartonStore = $this->handleCartonStore($data);

        if (!isset($cartonStore['Error'])) {

            if (isset($creator)) {

                if ($creator === 'customer') {
                    DB::beginTransaction();
                    try {
                        // Validar transacciones del cliente
                        $this->validateTransaction($creatorID);
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Ha ocurrido un error durante la transacción: ' . $e->getMessage());
                        return response()->json(['type' => 'warning', 'message' => 'Error en la transacción']);
                    }
                }

                $jackpot = number_format($this->convertPrizeValue($roomID, $coin, 'float'), 2, '.');

                return response()->json([
                    'jackpot' => $jackpot,
                    'ctns' => BingoRoomUser::getValueByColumn($roomID, $playerColumn, $player, 'ctns'),
                    'type' => 'success',
                    'message' => 'Proceso efectuado con exito'
                ]);
            } else {
                Session::flash('message', 'Proceso efectuado con exito');
                Session::flash('playerLink',  $cartonStore);
                return redirect()->back();
            }
        } else {
            if (isset($creator)) {
                return response()->json(['type' => 'warning', 'message' => $cartonStore['Error']]);
            } else {
                return redirect()->back()->with(['type' => 'warning', 'message' => 'Error al procesar la solicitud']);
            }
        }
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

                $moreViewData = ['custLogo' => Customer::getValueById($creatorID, 'logo')];

                if (isset($data['userToken'])) {
                    $user = CustomerUser::getByToken($creatorID, $data['userToken'], ['id', 'coin']);
                    $coinID = $user->coin;
                    $coinSym = $coinID;
                    $roomUser = BingoRoomUser::getValueByColumn($room->id, 'player_user', $user->id, 'id');
                } else {
                    $coinID = $data['coin'];
                    $coinSym = CustomerExchangeRate::getValueById($coinID, 'coin');
                }

                $moreViewData['player'] =  (isset($roomUser) && $roomUser) ? true : false;

                if ($room->status == 2) {
                    $moreViewData['customerName'] = Customer::getValueById($creatorID, 'name');
                }

                break;
            case 'RKE';
            case 'commerce':

                $creatorID = $creator['id'];

                if (isset($data['playerLink'])) {

                    $player = $data['playerLink'];

                    $roomID = PlayerLink::getValueByLink($player, 'room');

                    if (!$roomID) return ['error' => 'Sala no encontrada'];

                    $isLogin = false;

                    $playerLink = PlayerLink::select('id', 'country', 'link')->where('room', $roomID)->first();
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
                        $coinID = 1;
                        $coinSym = 'USDT';

                        $commerceCoinsAvailable = CommerceExchangeRate::getCoinsByCommerce($creatorID, 1);
                        $coinsAvailable = Coin::getCoinsById($commerceCoinsAvailable, ['id', 'symbol', 'long_name']);
                    }
                }

                $room = BingoRoom::getRoomById($roomID, ['status', 'room_type', 'ctn_price']);

                $moreViewData = [
                    'player' => (isset($roomUser) && $roomUser) ? true : false,
                    'saleLink' => true,
                    'coinsAvailable' => $coinsAvailable ?? []
                ];

                break;
            default:
                return false;
                break;
        }

        // Verificar Cierre de Sala
        $this->checkRoomClosure($roomID);

        $room = BingoRoom::select(['status', 'date', 'time', 'encrypted'])->where('id', $roomID)->first();

        if (!in_array($room->status, [2, 3, 4])) return ['error' => 'Sala no disponible'];

        // Definir precio del carton 
        $dataCtnPrice = [
            'coin' => $coinID,
            'value' => BingoRoom::getValueById($roomID, 'ctn_price'),
            'creator' => $creator
        ];

        $moreViewData['ctnPrice'] = $this->convertValueByGame('BINGO', $roomID, $dataCtnPrice, 'float');

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

        $infoGame = ($room->status >= 3) ? $this->getResult($room->encrypted, $roomUser ?? null, $randomKey) : [];

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
            'timeServ' => $dataChips['timeServ']
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
        if (!$room) $player = PlayerLink::getByLink($player, ['room', 'commerce', 'link']);

        $creator = ['type' => 'RKE', 'id' => $player->commerce];

        $viewData = self::getGameInfo($creator, ['playerLink' => $player->link, 'room' => $player->room]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function inGameCommerce($commerce, $playerLink)
    {
        // Vista para el juego de un comercio especifico
        $commerceID = PlayerLink::getValueByLink($playerLink, 'commerce');

        // Validar el codigo de comercio
        $comerceIDByUUID = Commerce::getValueByCodeUUID($commerce, 'id');

        if ($comerceIDByUUID != $commerceID) return redirect('/');

        $creator = ['type' => 'commerce', 'id' => $commerceID];

        $viewData = self::getGameInfo($creator, ['playerLink' => $playerLink]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function inGameCustomer($customerToken, $userToken, $room)
    {
        // Vista para el juego de un cliente especifico

        // Validar Informacion de cliente-user y crear variabales de session
        $response = $this->validateCustomer($customerToken, $userToken);

        if (isset($response['Error'])) {
            return response()->json(['message' => $response['Error']], 404);
        }

        $creator = ['type' => 'customer', 'id' => $response];

        // Obtener informacion de la vista del cliente
        $viewData = self::getGameInfo($creator, ['room' => $room, 'userToken' => $userToken]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function outGame($room = '', $creator = '')
    {
        // Vista del el juego solo para comprar [TODOS]

        $creator = ['type' => 'RKE', 'id' => $creator];

        $viewData = self::getGameInfo($creator, ['room' => $room]);

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function outGameCommerce($commerce, $room = '')
    {
        // Vista del el juego para un comercio especifico solo para comprar
        $commerce = Commerce::getValueByCodeUUID($commerce, 'id') ?? decrypt($commerce);

        // Redireccionar al Login si no hay coincidencia    
        if (!$commerce) return redirect()->back();

        if (!$room) {
            // Redirecciona si no se envio un ID de sala
            return redirect()->route('bingocash.out-game-commerce', ['commerce' => $commerce, 'room' => $room]);
        } else {

            $roomCommerce = BingoRoom::getRoomByEncrypted($room, ['id', 'commerce']);

            if (!$roomCommerce || ($roomCommerce && $roomCommerce->commerce != $commerce)) {
                if (!$commerce) return redirect()->back();
            }
        }

        $creator = ['type' => 'commerce', 'id' => $commerce];

        $viewData = self::getGameInfo($creator, ['room' => $room]);

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
        $room = BingoRoom::getRoomByEncrypted($room, ['id', 'status']);

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

    public function getResult($roomID, $roomUser, $randomKey)
    {
        // Obtener la información de los ganadores
        $winnersInfo = self::getInfoWinners($roomID, $roomUser);

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

    // CRUD //     
    public function getGameRoomsInfo($creator, $coin) //  Lista Salas CRUD de Juego
    {
        $this->checkRoomClosure();

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
                        'creatorType' => 'commerce',
                        'userID' => $creator['id']
                    ]
                ];
                $GMRoute =  route('bingo.game-rooms-commerce', ['coin' => encrypt($coin)]);

                $moreViewData = [
                    'commerceUUID' => Commerce::getValueById($creatorID, 'code_uuid'),
                    'wallet' => $this->wallet(),
                    'countries' => Country::getAllAvailableCountries()
                ];

                if ($creatorID == 1) $roomType = Room::getRoomTypes();

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
                        'creatorType' => 'customer',
                        'userID' => $creator['id']
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
        ])->where($creator['type'], $creatorID)->orderByDesc('date')
            ->orderByDesc('time')->orderBy('status')->get();

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Salas de Bingo',
            'name' => 'bingocash',
            'returnBtn' => $returnBtn ?? true,
            'routeWithCoin' => $routeWithCoin,
            'routeRKE' => $this->getRouteRKE(),
            'creator' => $creator,
            'coins' => $coins,
            'coin' => $coin,
            'coinSym' => $coinSym,
            'exchangeRate' => $exchangeRate ?? 1,
            'rooms' => $rooms,
            'GMRoute' => $GMRoute,
            'activeRooms' => $rooms->whereIn('status', [2, 3])->count(),
            'roomType' => $roomType ?? [],
            'jackpotType' => JackpotType::all(),
            'gameModes' => GameModes::all()
        ];

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.game.bingo.form.index', 'data' => $viewData];
    }

    public function allRooms($creatorType, $creatorID = '', $coin = '')
    {
        switch ($creatorType) {
            case 'admin':
                // Lógica para "admin" si se necesita.
                break;

            case 'commerce':
                return $this->allCommerceRooms($creatorID, $coin);

            case 'customer':
                return $this->allCustomerRooms($creatorID, $coin);

            default:
                return redirect()->back()->with('error', 'Tipo de creador no válido.');
        }
    }

    public function allCommerceRooms($creatorID, $coin = '')
    {
        $commerceId = Commerce::getValueByUserId(Auth::id(), 'id');

        if (!$commerceId) {
            return redirect()->back()->with('error', 'El comercio no existe o no está vinculado al usuario.');
        }

        if (!$creatorID || !$coin) {
            return redirect()->route('bingo.all-rooms', [
                'creatorType' => 'commerce',
                'userID' => encrypt($commerceId),
                'coin' => encrypt(1)
            ]);
        }

        $creator = [
            'type' => 'commerce',
            'id' => encrypt($commerceId),
        ];

        $viewData = $this->getGameRoomsInfo($creator, decrypt($coin));

        // Retorna la vista con la información
        return $this->handleViewData($viewData, $creator['type']);
    }


    public function allCustomerRooms($customer = '', $coin = '')
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
                'creatorType' => 'customer',
                'userID' => $isLogin ? encrypt($customerID) : $customerID,
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
                    'creatorType' => 'customer',
                    'userID' => $isLogin ? encrypt($customerID) : $customerID,
                ]
            );
        }

        $creator = ['type' => 'customer', 'id' => encrypt($customerID)];

        // Obtener la información de las salas de juego
        $viewData = $this->getGameRoomsInfo($creator, $coin);

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

            $boat = ($request->jackpot_type > 1) ? $request->fixed_jackpot_value : 0;
            $coin = $request->coin ?? 1; // ID Coin == 1 (USDT)

            $createData = [
                'game_mode' => $request->game_mode,
                'status' => 1,
                'creation' => 'M',
                'date' => $request->date,
                'time' => $request->time,
                'unix_date' => strtotime("{$request->date} {$request->time}"),
                'coin' => $coin,
                'ctn_price' => $request->ctn_price,
                'jackpot' => $boat,
                'encrypted' => $this->generateUniqueCode('bingo_rooms'),
                'room_type' => $request->room_type ?? 1
            ];

            if ($creatorType === 'commerce') {
                $createData['commerce'] = $creatorID;
                $createData['subsidiary'] = $request->subsidiary ?? null;
            } else {
                $createData['customer'] = $creatorID;
                $createData['code_uuid'] = $this->getCodeUUID('BingoRoom');
            }

            DB::beginTransaction();

            try {

                $roomID = BingoRoom::create($createData)->id;

                // Registar el creador de la sala
                BingoCreator::create([
                    'room' => $roomID,
                    'creator_type' => UserType::getValueByPrefix($creatorType, 'id'),
                    'creator_id' => $creatorID,
                    'creator_commn' => $request->room_commission,
                    'ctn_price' => $request->ctn_price,
                ]);

                BingoRoomConfig::create([
                    'room' => $roomID,
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
                DB::rollBack();
                Log::error('Error al crear la sala: ' . $e->getMessage());
                return redirect()->back()->with('msg-error', 'Hubo un problema al crear la sala: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('msg-error', 'No tienes permiso para crear una sala');
    }

    public function update(Request $request, $id)
    {
        $id = decrypt($id);
        $room = BingoRoom::findOrFail($id);

        date_default_timezone_set(config('app.timezone'));

        $newDate = $request->date;
        $newTime = $request->time;
        $unixDate = strtotime("{$newDate} {$newTime}");

        if ($unixDate < time()) {
            return redirect()->back()->with('message', 'Fecha/Hora no permitidas')->with('type', 'warning');
        }

        DB::beginTransaction();

        try {
            $boat = ($request->jackpot_type > 1) ? $request->fixed_jackpot_value : 0;
            $coin = $request->coin ?? 1;

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

            BingoCreator::where('room', $id)->update([
                'creator_commn' => $request->room_commission,
                'ctn_price' => $request->ctn_price,
            ]);

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
            DB::rollBack();
            Log::error('Hubo un problema al actualizar la información de la sala: ' . $e->getMessage());
            return redirect()->back()->with('message', 'Hubo un problema al actualizar la información')->with('type', 'warning');
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
        $roomID = BingoRoom::getValueByEncrypted($id, 'id');

        if (!$roomID) return response()->json(['Error' => 'Sala no encontrada']);

        $room = BingoRoom::select('game_mode', 'unix_date', 'ctn_price', 'coin', 'jackpot_prize')
            ->where('id', $roomID)->firstOrFail();

        $roomConfig = BingoRoomConfig::select('jackpot_type', 'fixed_jackpot_value', 'room_commn', 'serials_commn', 'val_serials', 'num_serials')
            ->where('room', $roomID)->firstOrFail();

        if ($mode) {

            $creator = BingoRoom::getRoomById($roomID, ['customer', 'commerce']);

            if ($creator->customer) {
                $isLogin = false;
                $custCoin = CustomerExchangeRate::getById(decrypt($coin), ['coin', 'exchange_rate']);
                $coin = $custCoin->coin;
                $exchangeRate = $custCoin->exchange_rate;
            } else {
                $coin = decrypt($coin);
                $isLogin = ($coin == 1) ? $this->wallet()->usdt : $this->wallet()->balance;
                $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($creator->commerce, $coin);
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

        if ($existingRoom->commerce) {
            $createData['commerce'] = $existingRoom->commerce;
            $createData['subsidiary'] = $existingRoom->subsidiary ?? null;
        } else {
            $createData['customer'] = $existingRoom->customer;
            $createData['code_uuid'] = $this->getCodeUUID('BingoRoom');
        }

        DB::beginTransaction();

        try {

            // Obtener ID de la nueva sala
            $newRoom = BingoRoom::create($createData)->id;

            // Registar el creador de la sala
            BingoCreator::create([
                'room' => $newRoom,
                'creator_type' => $existingRoom->commerce ? $existingRoom->commerce : $existingRoom->customer,
                'creator_id' => $existingRoom->commerce ? $existingRoom->commerce : $existingRoom->customer,
                'creator_commn' => $existingRoomConfig->room_commn,
                'ctn_price' => $existingRoom->ctn_price,
            ]);

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
        $playerLink = $request->playerLink;

        if (PlayerLink::where('link', $playerLink)->exists()) {
            $room = PlayerLink::where('link', $playerLink)->value('room');
            $this->newDraw($room, $playerLink);
            return redirect()->back()->with('message', 'Carton Ganador Generado');
        } else {
            return redirect()->back()->with('message', 'El PlayerLink no existe');
        }
    }

    public function getAutomaticRoomsInfo($creator)
    {
        //  Lista (CRUD) de configuracion de Salas Automaticas
        $this->checkRoomClosure();

        $creatorType = $creator['type'];
        $creatorID = decrypt($creator['id']);

        switch ($creatorType) {
            case 'admin': // Admin
                break;
            case 'commerce': // Comercio
                $isLogin = true;

                $commerce = Commerce::getById($creatorID, ['coin', 'code_uuid']);

                $GMRoute =  route('bingo.game-rooms-commerce', ['coin' => encrypt($commerce->coin)]);

                $moreViewData = [
                    'commerceUUID' => $commerce->code_uuid,
                    'wallet' => $this->wallet()
                ];

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

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Salas de Bingo AUTO',
            'name' => 'bingocash',
            'returnBtn' => $returnBtn ?? true,
            'creator' => $creator,
            'rooms' => $automaticRooms,
            'activeRooms' => $activeRooms,
            'routeGenerateBingoRoomsJob' => $routeExecuteGenerateBingoRoomsJob,
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

    public function allAutomaticRooms($creatorType, $creatorID)
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

    public function createAutomaticRoomInfo(Request $request)
    {
        $creator = json_decode($request->creator);
        $creatorType = UserType::getValueByPrefix($creator->type, 'id');
        $creatorID = decrypt($creator->id);

        DB::beginTransaction();

        try {
            BingoRoomAutomatic::create([
                'time' => $request->time,
                'game_mode' => $request->game_mode,
                'room_type' => $request->room_type ?? 1,
                'jackpot_type' => $request->jackpot_type,
                'fixed_jackpot_value' => $request->fixed_jackpot_value,
                'ctn_price' => $request->ctn_price,
                'room_commn' => $request->room_commission,
                'serials_commn' => $request->serials_commission,
                'val_serials' => $request->val_serials,
                'num_serials' => $request->num_serials,
                'creator_type' => $creatorType,
                'creator_id' => $creatorID,
            ]);
            DB::commit();
            return redirect()->back()->with('message', 'Registro creado con éxito')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear información de una sala automática: ' . $e->getMessage());
            return redirect()->back()->with('msg-error', 'Hubo un error al crear la información');
        }
    }

    public function updateAutomaticRoomInfo(Request $request, $id)
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
                'ctn_price' => $request->ctn_price,
                'room_commn' => $request->room_commission,
                'serials_commn' => $request->serials_commission,
                'val_serials' => $request->val_serials,
                'num_serials' => $request->num_serials,
            ]);
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
            'time',
            'game_mode',
            'room_type',
            'jackpot_type',
            'fixed_jackpot_value',
            'ctn_price',
            'room_commn',
            'serials_commn',
            'val_serials',
            'num_serials',
            'status'
        )->where('id', $id)->firstOrFail();

        return response()->json(['room' => $room]);
    }

    public function changeStatusAutomaticRoomInfo($id)
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

    public function deleteAutomaticRoomInfo($id)
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
}
