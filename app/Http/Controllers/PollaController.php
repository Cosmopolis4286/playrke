<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Coin;
use App\Models\Commerce;
use App\Models\CommerceCountriesCoins;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerExchangeRate;
use App\Models\CustomerGames;
use App\Models\GameModes;
use App\Models\Games;
use App\Models\JackpotType;
use App\Models\PlayHistory;
use App\Models\PollaCreator;
use App\Models\PollaDraw;
use App\Models\PollaDrawAutomatic;
use App\Models\PollaDrawConfig;
use App\Models\PollaLotteryResults;
use App\Models\PollaTicketPrice;
use App\Models\PollaTicketPriceAutomatic;
use App\Models\PollaTickets;
use App\Models\PollaTypes;
use App\Models\Room;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;
use App\Services\Game\Polla\HandlePollaDraws;
use App\Traits\CustomerTrait;
use App\Traits\EncryptRKE;
use App\Traits\Filters;
use App\Traits\GameTrait;
use App\Traits\PollaTrait;
use App\Traits\RouteRKE;
use App\Traits\UserVal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

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

    private function getRouteTicketPriceForCoin()
    {
        return route(
            'polla.getTicketPriceForCoin',
            ['creation' => ':1', 'creatorType' => ':2', 'creatorID' => ':3', 'draw' => ':4']
        );
    }

    public function getTicketPriceForCoin($creation, $creatorType, $creatorID, $draw = '')
    {
        // Desencriptar $creatorID
        $creatorID = decrypt($creatorID);

        // Obtener las monedas relevantes según el tipo de creador
        $coins = match ($creatorType) {
            'admin' => Coin::where('id', '!=', 1)->distinct()->pluck('coin'),
            'commerce' => CommerceCountriesCoins::getAvailableCoins($creatorID, 1),
            default => CustomerExchangeRate::getCoinsByCustomer($creatorID),
        };

        $coinsTicketPrice = collect();

        if ($draw) {
            if ($creation === 'M') {
                // Obtener ID del sorteo
                $drawID = PollaDraw::getValueByEncrypted($draw, 'id');

                // Parámetros del creador
                $creator = ['type' => $creatorType, 'id' => $creatorID];

                // Obtener ID Polla Creator-User
                $pollaCreatorUser = PollaCreator::getValueByCreatorDraw($creator, $drawID, 'creator_user');

                $coinsTicketPrice = PollaTicketPrice::getOnlyCoinsTicketPrice($drawID, $pollaCreatorUser ?? false);
            } else {
                $coinsTicketPrice = PollaTicketPriceAutomatic::getOnlyCoinsTicketPrice($draw ?? '');
            }
        }

        // Inicializamos un contador para las claves incrementales
        $counter = 0;

        // Obtener información completa de las monedas en `coinsTicketPrice`
        $presentCoinsData = $coinsTicketPrice->mapWithKeys(function ($ticketPrice, $coin) use (&$counter, $creatorType) {
            return [
                $counter++ => [
                    'coin' => $coin,
                    'isoCode' => $creatorType === 'customer'
                        ? $coin
                        : Coin::getValue($coin, 'iso_code'),
                    'ticketPrice' => $ticketPrice,
                ],
            ];
        });

        // Identificar monedas faltantes
        $missingCoins = collect($coins)->diff($coinsTicketPrice->keys());

        // Procesar las monedas faltantes
        $missingCoinsData = $missingCoins->mapWithKeys(function ($missingCoin) use (&$counter, $creatorType) {
            return [
                $counter++ => [
                    'coin' => "$missingCoin",
                    'isoCode' => $creatorType === 'customer'
                        ? $missingCoin
                        : Coin::getValue($missingCoin, 'iso_code'),
                    'ticketPrice' => '', // Precio vacío
                ],
            ];
        });

        // Combinar los datos y estructurarlos en un arreglo con identificador clave
        $arrCoinsTicketPrice = $presentCoinsData->merge($missingCoinsData);

        // Retornar respuesta con los datos
        return response()->json([
            'hasPrice' => count($presentCoinsData) ? true : false,
            'ticketPrice' => $arrCoinsTicketPrice
        ]);
    }

    private function getInfoRooms($creatorUser, $roomType, $coin)
    {
        $gameID = Games::getValueByName('BINGO');
        $roomType = $roomType === 'private-rooms' ? 'PV' : 'PB';

        return PollaDraw::with([
            'GameType',
            'Config.JackpotType',
            'Config:draw,game_mode,jackpot_type,ticket_price',
            'Config.GameMode:id,description',
            'Winners',
            'GameExchangeRate' => function ($query) use ($coin, $gameID) {
                $query->where('coin', $coin)->where('game', $gameID);
            }
        ])->where('creator_user', $creatorUser)->where('room_type', $roomType)
            ->orderByDesc('date')->orderByDesc('game_type')->get();
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

        $draws = $this->getInfoRooms($creator['userId'], $roomType, $coin);

        $routeGetTicketPriceForCoin = $this->getRouteTicketPriceForCoin();

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Salas de Juego',
            'name' => 'polla',
            'returnBtn' => $returnBtn ?? true,
            'routeWithCoin' => $routeWithCoin,
            'routeGetTicketPriceForCoin' => $routeGetTicketPriceForCoin,
            'routeRKE' => $this->getRouteRKE(),
            'creator' => $creator,
            'coins' => $coins,
            'coin' => $coin,
            'coinSym' => $coinSym,
            'exchangeRate' => $exchangeRate ?? 1,
            'draws' => $draws,
            'GMRoute' => $GMRoute,
            'activeRooms' => $draws->whereIn('status', [2, 3])->count(),
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
        // (new HandlePollaDraws())->checkRoomClosure();

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

        $automaticRooms = PollaDrawAutomatic::where('creator_user', $creator['userId'])
            ->whereNot('status', 3)
            ->get([
                'id',
                'time',
                'game_mode',
                'room_type',
                'jackpot_type',
                'fixed_jackpot_value',
                'ticket_price',
                'time_chip',
                'room_commn',
                'serials_commn',
                'pct_serials',
                'num_serials',
                'status'
            ]);

        $activeRooms = PollaDraw::where('creator_user', $creator['userId'])->whereIn('status', [2, 3])->exists();

        $routeExecuteGeneratePollaDrawsJob = route(
            'bingo.automatic-room.executeGeneratePollaDrawsJob',
            ['creatorType' => $creator['type'], 'creatorID' => $creator['id']]
        );

        $routeGetTicketPriceForCoin = $this->getRouteTicketPriceForCoin();

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Salas de Bingo',
            'name' => 'bingo',
            'returnBtn' => $returnBtn ?? true,
            'creator' => $creator,
            'rooms' => $automaticRooms,
            'activeRooms' => $activeRooms,
            'routeGeneratePollaDrawsJob' => $routeExecuteGeneratePollaDrawsJob,
            'routeGetTicketPriceForCoin' => $routeGetTicketPriceForCoin,
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
        $creator = json_decode($request->creator);
        $creatorType = $creator->type;
        $creatorID = decrypt($creator->id);
        $allowedPrefixes = ['admin', 'commerce', 'customer'];

        if (in_array($creatorType, $allowedPrefixes)) {

            date_default_timezone_set(config('app.timezone'));

            $userID = Auth::id();
            $fixedJackpotValue = $request->fixed_jackpot_value;

            $boat = $request->jackpot_type > 1 ? $fixedJackpotValue : 0;

            $model = 'PollaDraw';

            $createData = [
                'date' => $request->date,
                'game_type' => $request->game_type,
                'status' => 1,
                'room_type' => $request->room_type,
                'creator_user' => $userID,
                'jackpot' => $boat,
                'close_sale' => 2,
                'code_uuid' => $this->getCodeUUID($model)
            ];

            DB::beginTransaction();

            $hour = $this->getFormattedTime($request->game_type);

            try {
                $drawID = PollaDraw::create($createData)->id;

                PollaDrawConfig::create([
                    'draw' => $drawID,
                    'creation' => 'M',
                    'creator_user' => $userID,
                    'game_mode' => $request->game_mode,
                    'unix_date' => strtotime("{$request->date} {$hour}"),
                    'ticket_price' => $request->ticket_price,
                    'jackpot_type' => $request->jackpot_type,
                    'fixed_jackpot_value' => $request->fixed_jackpot_value,
                    'active_prizes' => 1,
                    'room_commn' => $request->room_commn,
                ]);

                PollaCreator::create([
                    'draw' => $drawID,
                    'creator_user' => $userID,
                    'creator_type' => UserType::getValueByPrefix($creatorType, 'id'),
                    'creator_id' => $creatorID,
                    'creator_commn' => $request->room_commn,
                    'ticket_price' => $request->ticket_price,
                ]);

                $ticketPriceCoins = $request->ticket_price_coins;

                if ($ticketPriceCoins) {

                    $timestamp = now();

                    $recordsToInsert = collect($ticketPriceCoins)
                        ->filter(fn($ticketPrice) => $ticketPrice) // Filtrar solo los elementos con valor
                        ->map(function ($ticketPrice, $coin) use ($drawID, $userID, $timestamp) {
                            return [
                                'draw' => $drawID,
                                'creator_user' => $userID,
                                'coin' => $coin,
                                'ticket_price' => $ticketPrice,
                                'created_at' => $timestamp,
                                'updated_at' => $timestamp,
                            ];
                        })->values()->toArray();

                    PollaTicketPrice::insert($recordsToInsert);
                }

                DB::commit();
                return redirect()->back()->with('message', 'Sala creada con éxito')->with('type', 'success');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al crear la sala de polla de animalitos: ' . $e->getMessage());
                return redirect()->back()->with(['type' => 'warning', 'message' => 'Error al crear la sala']);
            }
        }

        return redirect()->back()->with('msg-error', 'No tienes permiso para crear una sala');
    }

    public function update(Request $request, $id)
    {
        $drawID = decrypt($id);
        $draw = PollaDraw::findOrFail($drawID);

        date_default_timezone_set(config('app.timezone'));

        $newDate = $request->date;

        $hour = $this->getFormattedTime($request->game_type);

        $unixDate = strtotime("{$newDate} {$hour}");

        if ($unixDate < time()) {
            return redirect()->back()->with('message', 'Fecha/Hora no permitidas')->with('type', 'warning');
        }

        $fixedJackpotValue = $request->fixed_jackpot_value;

        $boat = $request->jackpot_type > 1 ? $fixedJackpotValue : 0;

        $updateDataDraw = ['room_type' => $request->room_type, 'jackpot' => $draw->jackpot + $boat];

        $updateDataConfig = [
            'unix_date' => $unixDate,
            'ticket_price' => $request->ticket_price,
            'jackpot_type' => $request->jackpot_type,
            'fixed_jackpot_value' => $request->fixed_jackpot_value,
            // 'active_prizes',
            'room_commn' => $request->room_commn
        ];

        // Solo añadir 'game_mode' si el all_sale es 0
        if ($draw->all_sale == 0) {
            $updateDataConfig['game_mode'] = $request->game_mode;
        }

        DB::beginTransaction();

        try {
            // Actualizar el bingo con los datos construidos
            $draw->update($updateDataDraw);

            PollaDrawConfig::where('draw', $drawID)->update($updateDataConfig);

            $pollaCreator = PollaCreator::where('draw', $drawID)->firstOrFail();

            $pollaCreator->update([
                'creator_commn' => $request->room_commn,
                'ticket_price' => $request->ticket_price,
            ]);

            $creatorUser = $draw->creator_user;

            $ticketPriceCoins = $request->ticket_price_coins;

            if ($ticketPriceCoins) {

                $timestamp = now();

                collect($ticketPriceCoins)->each(function ($ticketPrice, $coin) use ($drawID, $creatorUser, $timestamp) {
                    // Obtener el registro existente
                    $pollaTicketPrice = PollaTicketPrice::getTicketPrice($drawID, $coin, $creatorUser);

                    // Verificar si es necesario actualizar
                    if ($pollaTicketPrice && $pollaTicketPrice->ticket_price != $ticketPrice) {
                        // Construir datos para la actualización
                        $updateData = [
                            'ticket_price' => $ticketPrice,
                            'status' => $ticketPrice ? 1 : 0,
                            'updated_at' => $timestamp,
                        ];
                        // Actualizar el registro existente
                        PollaTicketPrice::where('id', $pollaTicketPrice->id)->update($updateData);
                    } else if (!$pollaTicketPrice && $ticketPrice) {
                        // Crear nuevo registro
                        PollaTicketPrice::create([
                            'draw' => $drawID,
                            'creator_user' => $creatorUser,
                            'coin' => $coin,
                            'ticket_price' => $ticketPrice,
                            'status' => 1
                        ]);
                    }
                });
            }

            DB::commit();

            return redirect()->back()->with('message', 'La información fue actualizada')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar la información de la sala de polla de animalitos: ' . $e->getMessage());
            return redirect()->back()->with(['type' => 'warning', 'message' => 'Error al actualizar la información']);
        }
    }

    public function getInfo($id, $starTime = '')
    {
        date_default_timezone_set(config('app.timezone'));

        if ($starTime) {
            $time = time();
            return response()->json($time);
        }

        $id = decrypt($id);

        $room = PollaDraw::select('date', 'game_type', 'players')->where('id', $id)->firstOrFail();

        $config = PollaDrawConfig::select(
            'creator_user',
            'game_mode',
            'ticket_price',
            'jackpot_type',
            'fixed_jackpot_value',
            'active_prizes',
            'room_commn'
        )->where('draw', $id)->firstOrFail();

        $pollaCreator = true;

        $creatorUserID = PollaDraw::getValueById($room, 'creator_user');
        $userType = User::getValueById($creatorUserID, 'user_type');

        $creatorType = UserType::getValueById($userType, 'prefix');

        if ($creatorType === 'commerce') {
            $creatorID = PollaCreator::getByDraw($id, 'creator_id');
            $creator = ['type' => $userType, 'id' => $creatorID];
            $pollaCreator = PollaCreator::getValueByCreatorDraw($creator, $id) ?? false;
        }

        return response()->json([
            'room' => $room,
            'config' => $config,
            'creator' => $pollaCreator
        ]);
    }

    public function copy($id)
    {
        $drawID = decrypt($id);

        $existingRoom = PollaDraw::findOrFail($drawID);
        $existingRoomConfig = PollaDrawConfig::where('draw', $drawID)->firstOrFail();
        $existingRoomCreator = PollaCreator::getByDraw($drawID);

        date_default_timezone_set(config('app.timezone'));

        $userID = $existingRoom->creator_user;
        $boat = $existingRoomConfig->jackpot_type > 1 ? $existingRoomConfig->fixed_jackpot_value : 0;

        $model = 'PollaDraw';

        $createData = [
            'date' => $existingRoom->date,
            'game_type' => $existingRoom->game_type,
            'status' => 1,
            'room_type' => $existingRoom->room_type,
            'creator_user' => $userID,
            'jackpot' => $boat,
            'close_sale' => 2,
            'code_uuid' => $this->getCodeUUID($model)
        ];

        // Definir $creator
        $creator = [
            'type' => $existingRoomCreator->creator_type,
            'id' => $existingRoomCreator->creator_id
        ];

        $pollaCreatorUser = PollaCreator::getValueByCreatorDraw($creator, $drawID, 'creator_user');
        $existingRoomTicketPrice = PollaTicketPrice::getAllTicketPrice($drawID, $pollaCreatorUser);

        $hour = $this->getFormattedTime($existingRoom->game_type);

        DB::beginTransaction();

        try {
            // Obtener la nueva sala
            $newDrawID = PollaDraw::create($createData)->id;

            $configData = PollaDrawConfig::create([
                'creator_user',
                'creation' => 'M',
                'creator_user' => $userID,
                'game_mode' => $existingRoomConfig->game_mode,
                'unix_date' => strtotime("{$existingRoom->date} {$hour}"),
                'ticket_price' => $existingRoomConfig->ticket_price,
                'jackpot_type' => $existingRoomConfig->jackpot_type,
                'fixed_jackpot_value' => $existingRoomConfig->fixed_jackpot_value,
                // 'active_prizes',
                'room_commn' => $existingRoomConfig->room_commn
            ]);

            PollaDrawConfig::create($configData);

            // Registar el creador de la sala
            PollaCreator::create([
                'draw' => $newDrawID,
                'creator_user' => $userID,
                'creator_type' => $existingRoomCreator->creator_type,
                'creator_id' => $existingRoomCreator->creator_id,
                'creator_commn' => $existingRoomCreator->room_commn,
                'ticket_price' => $existingRoomCreator->ticket_price
            ]);

            if ($existingRoomTicketPrice) {

                $timestamp = now();

                $recordsToInsert = collect($existingRoomTicketPrice)
                    ->filter(fn($ticketPrice) => $ticketPrice) // Filtrar solo los elementos con valor
                    ->map(function ($ticketPrice, $coin) use ($newDrawID, $userID, $timestamp) {
                        return [
                            'draw' => $newDrawID,
                            'creator_user' => $userID,
                            'coin' => $coin,
                            'ticket_price' => $ticketPrice,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    })->values()->toArray();

                PollaTicketPrice::insert($recordsToInsert);
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

    private function handleCreateAutomaticDraw($data, $replay = false)
    {
        DB::beginTransaction();

        try {
            $creator = json_decode($data->creator);

            $automaticBingoID = PollaDrawAutomatic::create([
                'time' => $data->time,
                'game_mode' => $data->game_mode,
                'room_type' => $data->room_type,
                'jackpot_type' => $data->jackpot_type,
                'fixed_jackpot_value' => $data->fixed_jackpot_value,
                'time_chip' => $data->time_chip,
                'ticket_price' => $data->ticket_price,
                'room_commn' => $data->room_commn,
                'serials_commn' => $data->serials_commission,
                'pct_serials' => $data->pct_serials,
                'num_serials' => $data->num_serials,
                'creator_type' => UserType::getValueByPrefix($creator->type, 'id'),
                'creator_id' => decrypt($creator->id),
                'creator_user' => Auth::id()
            ])->id;

            if (!empty($data->ticket_price_coins)) {
                $timestamp = now();

                $recordsToInsert = array_map(fn($coin, $ticketPrice) =>
                $ticketPrice ? [
                    'automatic_bingo_id' => $automaticBingoID,
                    'coin' => $coin,
                    'ticket_price' => $ticketPrice,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ] : null, array_keys((array) $data->ticket_price_coins), (array) $data->ticket_price_coins);

                PollaTicketPriceAutomatic::insert(array_filter($recordsToInsert));
            }

            DB::commit();
            return ['type' => 'success', 'message' => 'Registro creado con éxito'];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear sala automática: ' . $e->getMessage());
            return ['type' => 'warning', 'message' => 'Hubo un error al crear la sala'];
        }
    }

    public function replay($id)
    {
        $room = PollaDraw::with('Config')->findOrFail("id", decrypt($id));
        $response = $this->handleCreateAutomaticDraw($room, true);
        return redirect()->back()->with(['type' => $response['type'], 'message' => $response['message']]);
    }

    public function publish($id)
    {
        // Desencripta el ID y busca el registro correspondiente.
        $pollaDraw = PollaDraw::findOrFail(decrypt($id));

        date_default_timezone_set(config('app.timezone'));

        $hour = $this->getFormattedTime($pollaDraw->game_type);

        $unixDate = strtotime("{$pollaDraw->date} {$hour}");

        if ($unixDate < time()) {
            return redirect()->back()->with('message', 'Fecha/Hora no perimitidas')->with('type', 'warning');
        }

        DB::beginTransaction();

        try {
            // Actualiza el estado y la fecha de publicación.
            $pollaDraw->update(['status' => 2, 'publication_date' => Carbon::now()]);

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

        if (PollaDraw::getValueById($id, 'all_sale') > 0) {
            return redirect()->back()->with('msg-error', 'No es posible eliminar esta sala');
        }

        DB::beginTransaction();

        try {
            //Eliminar registro del Creador
            PollaCreator::where('draw', $id)->delete();
            // Eliminar configuracion de la sala
            PollaDrawConfig::where('draw', $id)->delete();

            // Actualizar el registro en bingo_rooms (o eliminar si decides hacerlo en lugar de solo actualizar el estado)
            PollaDraw::where('id', $id)->delete();

            DB::commit();
            // Redirigir de vuelta con un mensaje
            return redirect()->back()->with('message', 'La sala ha sido eliminada')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar la polla de animalitos: ' . $e->getMessage());
            return redirect()->back()->with('msg-error', 'Ha ocurrido un error');
        }
    }

    public function changeStatus($id)
    {
        $id = decrypt($id);
        PollaDraw::where('id', $id)->update(['status' => 1]);
        Session::flash('message', 'Status de la sala actualizado');
        return redirect()->back()->with('type', 'success');
    }

    public function refund($id) // Reembolsar compras de la sala
    {
        $drawID = decrypt($id);

        if (!PollaDrawUser::where('room', $drawID)->exists()) {
            return true;
        }

        $gameID = Games::getValueByName('BINGO');

        foreach (PollaDrawUser::where('room', $drawID)->cursor() as $player) {

            DB::beginTransaction(); // Iniciar transacción por cada iteración

            try {
                $user = $player->user;

                $amountToReturn = BingoCtnsLot::where('room', $drawID)
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
                    'room' => $drawID,
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
        PollaDraw::where('id', $drawID)->update(['status' => 5]);

        return redirect()->back()->with('message', 'El bote fue reembolsado')->with('type', 'warning');
    }

    public function reportSalesByDraw($drawID, $coin)
    {
        $commerce = $this->commerce(['id', 'user']);

        $room = PollaDraw::getByEncrypted($drawID, ['id', 'encrypted', 'creator_user']);

        if ($room->creator_user != $commerce->user || !$room->creator_user) {
            return redirect()->back();
        }

        $drawID = $room->id;

        $coin = decrypt($coin);

        $creatorUser = $commerce->user;
        $user = ['type' => 'commerce', 'id' => $commerce->id, 'userId' => $creatorUser];

        $existSale = $this->verifyExistenceOfSales($user, '', $coin, $drawID);

        if (!$existSale) {
            // si no existe venta para esa fecha
            return redirect()->route('commerce.reportSalesRooms', ['coin' => encrypt($coin), 'dateFilter' => 'all']);
        }

        $sellers = Seller::select('id', 'name', 'country')
            ->with([
                'Country:id,name',
                'CtnsLot' => fn($query) => $query->where([
                    ['room', $drawID],
                    ['coin', $coin]
                ])
            ])
            ->where('commerce', $commerce->id)
            ->whereHas('CtnsLot', fn($query) => $query->where([
                ['room', $drawID],
                ['coin', $coin]
            ]))->get();

        $coins = $this->getCommerceCoins($creatorUser, '', ['CtnsLot', 'Coin']);

        $routeWithCoin = [
            'route' => 'commerce.reportSalesByRoom',
            'parameters' => ['room' => $drawID]
        ];

        $coin = Coin::getById($coin, ['id', 'symbol']);

        $bingoConfig = PollaDrawConfig::getByRoom($drawID, ['ticket_price', 'room_commn']);

        return view('modules/commerce/games/bingo/report/by-room')->with([
            'title' => "Reporte de Sala",
            'cardTitle' => "<span> Reporte de Sala </span> <span class='badge badge-secondary'>{$room->encrypted}</span>",
            'ticketPrice' => $bingoConfig->ticket_price,
            'sellers' => $sellers,
            'comCommerceRm' => $bingoConfig->room_commn,
            'room' => $room->encrypted,
            'routeWithCoin' => $routeWithCoin,
            'coin' => $coin->id,
            'coinSym' => $coin->symbol,
            'coins' => $coins
        ]);
    }

    public function generatePlay()
    {
        $viewData = ['title' => 'Generar Jugada'];
        return view('generate-play')->with($viewData);
    }

    public function generateTicket(Request $request) {}
}
