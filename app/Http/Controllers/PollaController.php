<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BingoRoom;
use App\Models\Coin;
use App\Models\Commerce;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerExchangeRate;
use App\Models\CustomerGames;
use App\Models\GameModes;
use App\Models\Games;
use App\Models\JackpotType;
use App\Models\PollaDraw;
use App\Models\PollaLotteryResults;
use App\Models\PollaTickets;
use App\Models\PollaTypes;
use App\Services\Game\Polla\HandlePollaDraws;
use App\Traits\CustomerTrait;
use App\Traits\EncryptRKE;
use App\Traits\Filters;
use App\Traits\GameTrait;
use App\Traits\PollaTrait;
use App\Traits\RouteRKE;
use App\Traits\UserVal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PollaController extends Controller
{
    use EncryptRKE, UserVal, CustomerTrait, PollaTrait, GameTrait, RouteRKE, Filters;

    public function index()
    {
        // Obtener ultimo juego/polla/ en curso
        $draw = PollaDraw::orderByDesc()->first();

        // Definir variables
        $drawID = $draw->id;
        $date = $draw->date;

        $typeGame = PollaTypes::all();
        $titleTypeGame = PollaTypes::getValueById($draw->type_game, 'name');

        $lotteryResults = PollaLotteryResults::getLotteryResult($date);
        $tickets = PollaTickets::all();
        $firstAccumulated = 100;
        $secondAccumulated = 80;
        $weeklyAccumulated = 120;

        $ticketValue = 60;

        $coins = Coin::getByIdForStatus(['=', 1]);
        $tickets = PollaTickets::getAllByDraw($drawID);

        $viewData = [
            'draw' => $draw,
            'titleTypeGame' => $titleTypeGame,
            'lotteryResults' => $lotteryResults,
            'firstccumulated' => $firstAccumulated,
            'secondAccumulated' => $secondAccumulated,
            'weeklyAccumulated' => $weeklyAccumulated,
            'ticketValue' => $ticketValue,
            'coins' => $coins,
            'typeGame' => $typeGame,
            'tickets' => $tickets
        ];

        return view('game')->with($viewData);
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

    private function getRouteCtnPriceForCoin()
    {
        return route(
            'bingo.getCtnPriceForCoin',
            ['creation' => ':1', 'creatorType' => ':2', 'creatorID' => ':3', 'room' => ':4']
        );
    }

    private function getInfoRooms($creatorUser, $roomType, $coin)
    {
        $gameID = Games::getValueByName('BINGO');
        $roomType = $roomType === 'private-rooms' ? 'PV' : 'PB';

        return BingoRoom::with([
            'Config.JackpotType',
            'Config:room,game_mode,jackpot_type,date,time,unix_date',
            'Config.GameMode:id,description',
            'PlayersRoom' => function ($query) {
                $query->where('winner', '>', 0);
            },
            'GameExchangeRate' => function ($query) use ($coin, $gameID) {
                $query->where('coin', $coin)->where('game', $gameID);
            }
        ])->where('creator_user', $creatorUser)->where('room_type', $roomType)
            ->orderByRaw("(SELECT CONCAT(date, ' ', time) FROM bingo_rooms_config WHERE bingo_rooms_config.room = bingo_rooms.id LIMIT 1)")
            ->get();
    }

    private function getGameRoomsInfo($creator, $coin, $roomType)
    {
        // (new HandlePollaDraws())->checkRoomClosure();

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
                    'route' => 'polla.all-rooms',
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

                $token = Customer::getValueById($creatorID, 'token');

                $routeWithCoin = [
                    'route' => 'polla.all-rooms',
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

        $rooms = $this->getInfoRooms($creator['userId'], $roomType, $coin);

        $routeGetCtnPriceForCoin = $this->getRouteCtnPriceForCoin();

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Salas de Juego',
            'name' => 'polla',
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
            'gameType' => PollaTypes::all(),
            'jackpotType' => JackpotType::all(),
            'gameModes' => GameModes::all()
        ];

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.game.polla.form.' . $view, 'data' => $viewData];
    }

    public function getAutomaticRoomsInfo($creator)
    {
        //  Lista (CRUD) de configuracion de Salas Automaticas
        // (new HandleBingoRooms())->checkRoomClosure();

        $creatorType = $creator['type'];
        $creatorID = decrypt($creator['id']);

        switch ($creatorType) {
            case 'admin': // Admin
                break;
            case 'commerce': // Comercio
                $isLogin = true;

                $commerce = Commerce::getById($creatorID, ['user', 'coin', 'code_uuid']);

                $GMRoute =  route('bingo.game-rooms-commerce', ['roomType' => 'public', 'coin' => encrypt($commerce->coin)]);

                if ($creatorID == 1) $roomType = Room::getRoomTypes();

                break;
            case 'customer': // Cliente

                $isLogin = $returnBtn = false;

                $customer = Customer::getById($creatorID, ['user', 'token']);
                $token = $customer->token;

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

        $routeExecuteGenerateBingoRoomsJob = route(
            'bingo.automatic-room.executeGenerateBingoRoomsJob',
            ['creatorType' => $creator['type'], 'creatorID' => $creator['id']]
        );

        $routeGetCtnPriceForCoin = $this->getRouteCtnPriceForCoin();

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Salas de Bingo',
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

    private function allCommerceRooms($roomType, $creatorID, $coin = '')
    {
        $commerceId = Commerce::getValueByUserId(Auth::id(), 'id');

        if (!$commerceId) {
            return redirect()->back()->with('error', 'El comercio no existe o no está vinculado al usuario.');
        }

        if (!$creatorID || !$coin) {
            return redirect()->route('polla.all-rooms', [
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
        dd($request);
        DB::beginTransaction();

        try {
            PollaDraw::create([
                'date',
                'game_schedule',
                'close_sale',
                'status',
                'players',
                'plays',
                'paid_win',
                'all_sale',
                'rke_commn',
                'creator_commn',
                'seller_commn',
                'jackpot',
                'code_uuid'
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function update(Request $request, $id) {}

    public function generatePlay()
    {
        $viewData = ['title' => 'Generar Jugada'];
        return view('generate-play')->with($viewData);
    }

    public function generateTicket(Request $request) {}
}
