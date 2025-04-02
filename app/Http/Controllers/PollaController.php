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
use App\Models\UserType;
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
            'Config.JackpotType',
            'Config:draw,game_mode,jackpot_type',
            'Config.GameMode:id,description',
            'PlayersRoom' => function ($query) {
                $query->where('winner', '>', 0);
            },
            'GameExchangeRate' => function ($query) use ($coin, $gameID) {
                $query->where('coin', $coin)->where('game', $gameID);
            }
        ])->where('creator_user', $creatorUser)->where('room_type', $roomType)
            // ->orderByRaw("(SELECT CONCAT(date, ' ', time) FROM polla_draws_config WHERE polla_draws_config.draw = bingo_rooms.id LIMIT 1)")
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
                'ctn_price',
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
                'code_uuid' => $this->getCodeUUID($model)
            ];

            DB::beginTransaction();

            try {
                $drawID = PollaDraw::create($createData)->id;

                PollaDrawConfig::create([
                    'draw' => $drawID,
                    'creator_user' => $userID,
                    'game_mode' => $request->game_mode,
                    'ticket_price' => $request->ticket_price,
                    'jackpot_type' => $request->jackpot_type,
                    'fixed_jackpot_value' => $request->fixed_jackpot_value,
                    'active_prizes' => 1,
                    'room_commn' => $request->room_commn,
                ]);

                PollaCreator::creator([
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
        $newTime = $request->time;
        $unixDate = strtotime("{$newDate} {$newTime}");

        if ($unixDate < time()) {
            return redirect()->back()->with('message', 'Fecha/Hora no permitidas')->with('type', 'warning');
        }

        $fixedJackpotValue = $request->fixed_jackpot_value;

        $boat = $request->jackpot_type > 1 ? $fixedJackpotValue : 0;
        $coin = $request->coin ?? 1;

        $updateDataDraw = ['room_type' => $request->room_type, 'jackpot' => $draw->jackpot + $boat];

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

        // Solo añadir 'game_mode' si el jackpot es 0
        if ($draw->jackpot == 0) {
            $updateDataConfig['game_mode'] = $request->game_mode;
        }

        DB::beginTransaction();

        try {
            // Actualizar el bingo con los datos construidos
            $draw->update($updateDataDraw);

            PollaDrawConfig::where('draw', $drawID)->update($updateDataConfig);

            $bingoCreator = PollaCreator::where('draw', $drawID)->firstOrFail();

            $bingoCreator->update([
                'creator_commn' => $request->room_commn,
                'ctn_price' => $request->ctn_price,
            ]);

            $creatorUser = $draw->creator_user;

            $ticketPriceCoins = $request->ticket_price_coins;

            if ($ticketPriceCoins) {

                $timestamp = now();

                collect($ticketPriceCoins)->each(function ($ticketPrice, $coin) use ($drawID, $creatorUser, $timestamp) {
                    // Obtener el registro existente
                    $pollaTicketPrice = PollaTicketPrice::getTicketPrice($drawID, $coin, $creatorUser);

                    // Verificar si es necesario actualizar
                    if ($pollaTicketPrice && $pollaTicketPrice->ctn_price != $ticketPrice) {
                        // Construir datos para la actualización
                        $updateData = [
                            'ctn_price' => $ticketPrice,
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
            return redirect()->back()->with(['type' => 'warning', 'message', 'Error al actualizar la información']);
        }
    }

    public function generatePlay()
    {
        $viewData = ['title' => 'Generar Jugada'];
        return view('generate-play')->with($viewData);
    }

    public function generateTicket(Request $request) {}
}
