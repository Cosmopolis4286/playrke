<?php

namespace App\Http\Controllers;

use App\Traits\EncryptRKE;
use App\Models\User;
use App\Models\Coin;
use App\Models\Seller;
use App\Models\BingoCtns;
use App\Models\BingoCtnsLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Models\PagoMovil;
use App\Models\BingoPrizeCtns;
use App\Models\BingoPrizeCtnsPayment;
use App\Models\BingoRoom;
use App\Models\BingoRoomConfig;
use App\Models\Commerce;
use App\Models\CommerceCountriesCoins;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\SellerCountriesCoins;
use App\Models\UserType;
use App\Models\UUIDCodes;
use App\Models\Wallet;
use App\Services\Game\Bingo\HandleBingoRooms;
use App\Traits\BingoTrait;
use App\Traits\Filters;
use App\Traits\RouteRKE;
use App\Traits\UserVal;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CommerceController extends Controller
{
    use EncryptRKE, UserVal, RouteRKE, BingoTrait, Filters;

    private function validateRequest($request, $type = 1, $id = '')
    {
        // Si es tipo 2, encuentra al vendedor usando el ID descifrado.
        if ($type == 2) {
            $commerce = Commerce::findOrFail(decrypt($id));
        }

        // Definir reglas de validación.
        $rules = [
            'username' => ['required', 'string', 'max:20', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:40', 'unique:users,email'],
            'country' => ['required', 'numeric', 'exists:countries,id'],
            'coin' => ['required', 'numeric', 'exists:coins,id'],
            'name' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:20', 'unique:commerces,phone'],
            'other_coins.*' => ['exists:coins,id'],
        ];

        // Ajustar reglas de validación si es una actualización.
        if ($type == 2) {
            $rules['username'] = ['required', 'string', 'max:20', 'unique:users,username,' . $commerce->user];
            $rules['email'] = ['required', 'string', 'email', 'max:40', 'unique:users,email,' . $commerce->user];
            $rules['phone'] = ['required', 'string', 'max:20', 'unique:commerces,phone,' . $commerce->id];
        }

        // Definir mensajes de error personalizados.
        $messages = [
            'username.unique' => 'Nombre de usuario no disponible.',
            'email.unique' => 'Este correo ya está registrado.',
            'phone.unique' => 'Este celular (' . $request->phone . ') ya está registrado.',
            'other_coins.*.exists' => 'Uno o más de los otros coins seleccionados no existen en la base de datos.',
        ];

        // Crear el validador.
        $validator = Validator::make($request->all(), $rules, $messages);

        // Si la validación falla.
        if ($validator->fails()) {
            Session::flash('msg-error', $type);
            if ($type > 1) {
                Session::flash('encryptedId', $id);
            }
            return $validator; // Devuelve el validador en caso de falla
        }

        return $validator; // Devuelve el validador en caso de éxito
    }

    private function insertOtherCoins($commerce, $otherCoins)
    {
        if (!empty($otherCoins) && is_array($otherCoins)) {

            // Obtener las monedas seleccionadas y prepararlas para la inserción
            $otherCoins = Coin::getCoinsById($otherCoins);
            $coinsToCreate = [];

            foreach ($otherCoins as $coin) {
                $coinsToCreate[] = [
                    'commerce' => $commerce,
                    'country' => $coin->country,
                    'coin' => $coin->id,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            // Manejo de transacción para insertar las nuevas monedas
            DB::transaction(function () use ($commerce, $coinsToCreate) {
                try {
                    foreach ($coinsToCreate as $coinData) {
                        // Insertar la moneda si no existe para ese vendedor
                        CommerceCountriesCoins::firstOrCreate(
                            ['commerce' => $commerce, 'coin' => $coinData['coin']],
                            $coinData
                        );
                    }

                    // Habilitar solo las monedas seleccionadas
                    CommerceCountriesCoins::where('commerce', $commerce)
                        ->whereIn('coin', array_column($coinsToCreate, 'coin'))->update(['status' => 1]);
                } catch (\Exception $e) {
                    // Captura de cualquier otra excepción
                    DB::rollBack();
                    Log::error('Error general al insertar monedas para el Comercio: ' . $e->getMessage());
                    throw $e;
                }
            });
        }
    }

    public function create(Request $request) /// Registar Comercializadoras
    {
        if ($this->user(['user_type'])->user_type != 2) {
            return redirect()->back()->withErrors(['error' => 'No tienes permiso para realizar esta acción.']);
        }

        $codeRKE = $request->code_rke;
        $existsCodeRKE = Commerce::existsValue('code_rke', $codeRKE);

        if ($existsCodeRKE) {
            return redirect()->back()->with('message', 'El código RKE ya existe')->with('type', 'warning');
        }

        DB::beginTransaction();

        try {
            // Validar la solicitud usando la función validateRequest
            $validator = $this->validateRequest($request);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $newPassw = $this->valEncrypt();

            $newUser = User::create([
                'username' => $request->username,
                'user_type' => UserType::getValueByPrefix('commerce', 'id'),
                'email' => $request->email,
                'password' => Hash::make($newPassw),
                'country' => $request->country,
                'email_verified_at' => Carbon::now(),
                'referral_code' => User::getUniqueReferralCode(),
            ]);

            // Crear un nuevo Wallet para el usuario
            Wallet::create(['coin' => $request->coin, 'user' => $newUser->id]);

            $commerce = Commerce::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'coin' => $request->coin,
                'country' => $request->country,
                'status' => 1,
                'user' => $newUser->id,
                'code_rke' => $this->replaceLettersWithNumbers($codeRKE),
                'code_uuid' => $this->getCodeUUID('Commerce')
            ]);

            // Registar moneda Principal
            CommerceCountriesCoins::create([
                'commerce' => $commerce->id,
                'country' => $commerce->country,
                'coin' => $commerce->coin,
                'status' => 1
            ]);

            $otherCoins = $request->other_coins;

            if (!empty($otherCoins) && is_array($otherCoins)) {
                $otherCoins = Coin::getCoinsById($otherCoins);
                $timestamp = now();

                $coinsToCreate = [];
                foreach ($otherCoins as $coin) {
                    $coinsToCreate[] = [
                        'commerce' => $commerce->id,
                        'country' => $coin->country,
                        'coin' => $coin->id,
                        'status' => 1,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp
                    ];
                }
                CommerceCountriesCoins::insert($coinsToCreate);
            }

            // Registrar Code UUID
            UUIDCodes::create([
                'code_uuid' => $commerce->code_uuid,
                'user_type' => UserType::getValueByPrefix('commerce', 'id')
            ]);

            DB::commit();

            // Mensajes flash con tiempo de expiración personalizado
            Session::flash('message', 'Comercio ' . $request->name . ' registrado con éxito');
            Session::flash('message-passw', $newPassw);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error general: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Hubo un error al procesar la solicitud.']);
        }

        return redirect()->back();
    }

    public function getInfo($id) // Obtener Info Comercializadora
    {
        $id = decrypt($id);
        $commerce = Commerce::getById($id, ['name', 'country', 'coin', 'phone', 'code_rke']);
        $otherCoins = CommerceCountriesCoins::getAvailableCoins($id);
        $user = User::getById(Commerce::getValueById($id, 'user'), ['username', 'email']);

        $data = [
            'commerce' => $commerce,
            'user' => $user,
            'other_coins' => $otherCoins
        ];

        return response()->json($data);
    }

    public function update(Request $request, $id) // Editar Comercializadora
    {
        if ($this->user(['user_type'])->user_type != 2) {
            return redirect()->back()->withErrors(['error' => 'No tienes permiso para realizar esta acción.']);
        }

        // Decryptar el ID y encontrar el vendedor
        $commerce = Commerce::findOrFail(decrypt($id));

        $codeRKE = $request->code_rke;

        $existsCodeRKE = Commerce::where('code_rke', $codeRKE)
            ->where('id', '<>', $commerce->id)->exists();

        if ($existsCodeRKE) {
            return redirect()->back()->with('message', 'El código RKE ya existe')->with('type', 'warning');
        }

        DB::beginTransaction();

        try {

            if ($commerce) {
                $validator = $this->validateRequest($request, 2, $id);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator)->withInput();
                }

                // Verificar si la moneda principal ha cambiado
                $coinChange = $commerce->coin !== $request->coin;

                // Actualizar los campos del vendedor
                $commerce->update([
                    'name' => $request->name,
                    'country' => $request->country,
                    'coin' => $request->coin,
                    'phone' => $request->phone,
                    'code_rke' => $codeRKE
                ]);

                if ($coinChange) {
                    // Actualizar moneda Principal
                    CommerceCountriesCoins::where('commerce', $commerce->id)
                        ->where('coin', $commerce->coin)->update([
                            'country' => $commerce->country,
                            'coin' => $commerce->coin
                        ]);
                }

                $otherCoins = $request->other_coins;
                $this->insertOtherCoins($commerce->id, $otherCoins);

                DB::commit();

                Session::flash('message', 'La información fue actualizada correctamente');
            } else {
                // Manejar caso donde no se encuentra el vendedor
                Session::flash('error', 'No se encontró el vendedor');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            // Captura cualquier excepción lanzada durante el proceso de actualización
            Session::flash('error', 'Hubo un problema al actualizar la información del vendedor: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function delete($id)
    {
        //Commerce::destroy(decrypt($request->id));
        Commerce::where('id', decrypt($id))->update(['status' => 3]);
        Session::flash('message', 'El Comercio fue eliminado');
        return redirect()->back();
    }

    public function changeStatus($id)
    {
        $id = decrypt($id);
        $commerceStatus = (int)!Commerce::getValueById($id, 'status');
        Commerce::where('id', $id)->update(['status' => $commerceStatus]);

        Session::flash('message', 'Status actualizado');
        return redirect()->back()->with('type', 'success');
    }

    public function changePassword($id)
    {
        $res = $this->handleChangePassword('Commerce', $id);

        if (isset($res['PWD'])) {
            $password = $res['PWD'];
            Session::flash('message', "La nueva contraseña del Comercio es: <b>$password</b>");
            Session::flash('message-passw', $password);
        } else {
            Session::flash('msg-error', 'Ha ocurrido un error');
        }
        return redirect()->back();
    }

    public function allDirectories()
    {
        $title = 'Datos Bancarios';

        $pagoMovils = PagoMovil::with(['DocumentType', 'Bank'])->where("user", Auth::id())->get();

        return view('modules.commerce.directory.Venezuela.index', compact('title', 'pagoMovils'));
    }

    private function getCommerceCoins($commerce, $dateFilter = "today", $relation = [])
    {
        $query = CommerceCountriesCoins::with($relation[0])
            ->whereHas($relation[0], function ($subquery) use ($commerce, $relation, $dateFilter) {
                $subquery->whereColumn('coin', 'commerces_countries_coins.coin')
                    ->where('commerce', $commerce);

                if (count($relation) > 1) {
                    $subquery->with($relation[1]);
                }

                $subquery = $this->applyDateFilter($dateFilter, $subquery);
            });

        $coins = $query->where('status', 1)->orderBy('country')->pluck('coin');

        return Coin::getCoinsById($coins);
    }

    public function reportSalesRooms($coin = '', $dateFilter = "today")
    {
        $title = 'Reporte de Ventas';

        $commerce = $this->commerce('id')->id;

        $verifyCoin = $this->verifyExistenceOfSales($coin, ['type' => 'commerce', 'id' => $commerce]);

        $existSale = $this->existsCoin(BingoCtnsLot::where('commerce', $commerce), $coin, $dateFilter);

        if (!$existSale && $dateFilter !== 'all') $verifyCoin['return'] = true;

        if ($verifyCoin['return']) {
            return redirect()->route(
                'commerce.reportSalesRooms',
                ['coin' => encrypt($verifyCoin['coin']), 'dateFilter' => 'all']
            );
        }

        $wallet = $this->wallet();

        $coin = $verifyCoin['coin'];

        $dataCoin = Coin::getById($coin, ['symbol', 'short_name', 'iso_code']);
        $coinSym = $dataCoin->symbol;
        $coinName = "$dataCoin->short_name ($dataCoin->iso_code)";

        // Obtener los datos de los ganadores
        $rooms = $this->getRoomsData($commerce, $coin, $dateFilter);

        $coins = $this->getCommerceCoins($commerce, $dateFilter, ['CtnsLot', 'Coin']);

        // Inicialización de los totales
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
            $allSaleRoom = $room->CtnsLot->sum('sale_amount');
            $finalSaleRoom = $room->CtnsLot->sum('final_sale_amount');
            $comSaleSellerRoom = $allSaleRoom - $finalSaleRoom;
            $comRKE = 0; // Actualiza con tu lógica real
            $preJackpot = $allSaleRoom - $comSaleSellerRoom - $comRKE;
            $comBancaRoom = round($preJackpot * ($room->Config->room_commn / 100), 2);
            $jackpotRoom = $preJackpot - $comBancaRoom;

            $totals['allCtns'] += $room->CtnsLot->sum('ctns');
            $totals['allSales'] += $allSaleRoom;
            $totals['comSaleSeller'] += $comSaleSellerRoom;
            $totals['finalSale'] += $finalSaleRoom;
            $totals['jackpot'] += $jackpotRoom;
            $totals['comBanca'] += $comBancaRoom;

            $roomData[] = [
                'encrypted' => $room->encrypted,
                'date' => $room->date,
                'time' => $room->time,
                'allSaleRoom' => $allSaleRoom,
                'finalSaleRoom' => $finalSaleRoom,
                'comSaleSellerRoom' => $comSaleSellerRoom,
                'comBancaRoom' => $comBancaRoom,
                'jackpotRoom' => $jackpotRoom,
                'allCtns' => $room->CtnsLot->sum('ctns')
            ];
        }

        $routeWithCoin = [
            'route' => 'commerce.reportSalesRooms',
            'parameters' => ['dateFilter' => $dateFilter]
        ];

        return view(
            'modules.commerce.games.bingo.report.rooms',
            compact('title', 'wallet', 'rooms', 'routeWithCoin', 'dateFilter', 'coin', 'coins', 'coinSym', 'coinName', 'totals', 'roomData')
        );
    }

    private function getRoomsData($commerce, $coin, $dateFilter = "today")
    {
        $query = BingoRoom::with([
            'Config',
            'Status',
            'CtnsLot' => function ($subquery) use ($commerce, $coin) {
                $subquery->where('commerce', $commerce)
                    ->where('coin', $coin)->with('Coin');
            }
        ]);

        $query = $this->applyDateFilter($dateFilter, $query, 'publication_date');

        $rooms = $query->where('commerce', $commerce)
            // ID Commerce 1 es PlayRKE Sala Para todos
            ->get(['id', 'status', 'encrypted', 'time', 'date', 'jackpot', 'ctn_price']);

        return $rooms;
    }


    public function reportSalesByRoom($roomID, $coin)
    {
        $commerce = $this->commerce('id')->id;

        $room = BingoRoom::getRoomByEncrypted($roomID, ['id', 'ctn_price', 'encrypted', 'commerce']);

        if ($room->commerce != $commerce || !$room->commerce) {
            return redirect()->back();
        }

        $coin = decrypt($coin);

        // Verificar la existencia de la moneda
        $query = BingoCtnsLot::where('commerce', $commerce)->where('room', $room->id);
        $existSale = $this->existsCoin($query, $coin);

        if (!$existSale) {
            // si no existe venta para esa fecha
            return redirect()->route('commerce.reportSalesRooms', ['coin' => encrypt($coin), 'dateFilter' => 'all']);
        }

        $sellers = Seller::with([
            'Country',
            'CtnsLot' => function ($query) use ($room, $coin) {
                $query->where('room', $room->id)->where('coin', $coin);
            }
        ])->whereHas('CtnsLot', function ($query) use ($room, $coin) {
            $query->where('room', $room->id)->where('coin', $coin);
        })->where('commerce', $commerce)->get();

        $coins = $this->getCommerceCoins($commerce, '', ['CtnsLot', 'Coin']);

        $routeWithCoin = [
            'route' => 'commerce.reportSalesByRoom',
            'parameters' => ['room' => $roomID]
        ];

        $coin = Coin::getById($coin, ['id', 'symbol']);

        return view('modules/commerce/games/bingo/report/by-room')->with([
            'title' => "Reporte de Sala",
            'cardTitle' => "<span> Reporte de Sala </span> <span class='badge badge-secondary'>{$room->encrypted}</span>",
            'ctnPrice' => $room->ctn_price,
            'sellers' => $sellers,
            'comCommerceRm' => BingoRoomConfig::getValueByRoom($room->id, 'room_commn') / 100,
            'room' => $room->encrypted,
            'routeWithCoin' => $routeWithCoin,
            'coin' => $coin->id,
            'coinSym' => $coin->symbol,
            'coins' => $coins
        ]);
    }

    public function sellers()
    {
        $title = 'Mis Vendedores';
        $name = 'seller';
        $commerce = $this->commerce()->id;

        $sellers = Seller::with(['Country', 'CountriesCoins.Coin'])->where('commerce', $commerce)->get();

        $countries = $this->getAvailableCountries(Auth::id());
        $countriesCommerce = Country::getCountries($countries, ['id', 'name']);

        $coins = $this->getAvailableCoins(Auth::id());
        $coinsCommerce = Coin::getCoinsById($coins, ['id', 'symbol', 'short_name', 'long_name', 'country']);

        return view(
            'modules.commerce.sellers.index',
            compact('title', 'name', 'sellers', 'countriesCommerce', 'coinsCommerce')
        );
    }

    public function getCoinByCountry($country, $seller)
    {
        $coin = Coin::getCoinByCountry($country);

        $coinsSeller = SellerCountriesCoins::getCoinsByIdBySeller($seller);

        $othersCoins = Coin::getCoinsById($coinsSeller);

        return json_encode(array('coin' => $coin, 'othersCoins' => $othersCoins));
    }

    public function getSellerInfoByCtn($ctn) // Obtener informacion del Vendedor por un Carton Vendido
    {
        try {
            $ctnId = decrypt($ctn);
            $ctn = BingoCtns::getById($ctnId, ['seller', 'coin']);

            $seller = Seller::getById($ctn->seller, ['name', 'phone', 'user']);
            // Obtener detalles de pago del vendedor usando el ID del usuario autenticado y la moneda decriptada
            $data = $this->getPaymentDetailsByUserId($seller->user, $ctn->coin);

            // Verificar si se obtuvieron datos y retornar una respuesta JSON adecuada
            if ($data) {
                return response()->json(['data' => $data, 'seller' => [$seller->name, $seller->phone]]);
            } else {
                Session::flash('msg-error', 'Datos de pago no encontrados');
                return response()->json(['error' => 'Datos de pago no encontrados.'], 404);
            }
        } catch (\Exception $e) {
            // Manejar cualquier excepción que ocurra y retornar un error del servidor
            return response()->json(['error' => 'Ha ocurrido un error al procesar la solicitud.'], 500);
        }
    }

    public function updateCommissionForAllSellers(Request $request)
    {
        Seller::where('commerce', $request->id_comm)->update(['com' => $request->commn]);
    }

    public function getBingoWinnersByCommerce($dateFilter = 'all')
    {
        $title = 'Últimos Ganadores';
        $name = 'notify-payment-win-ctns-off';
        $wallet = $this->wallet();

        $commerce = $this->commerce()->id;

        app(HandleBingoRooms::class)->checkRoomClosure();

        // Obtener los datos de los ganadores
        $winners = $this->getWinnersData($commerce, $dateFilter);

        // Pagos Pendientes de otros comercios
        $paymentsOtherCommerces = 0;

        // Contar los ganadores pendientes de pago para otros comercios
        $countWinnersOtherCommerces = BingoPrizeCtns::getPendingPaymentsCountExcludingCommerce($commerce);

        // Contar los ganadores pendientes de pago para el propio comercio
        $countWinnersByCommerce = BingoPrizeCtns::getPendingPaymentsCountByCommerce($commerce);

        // Obtener las monedas asociadas al comercio
        $coins = $this->getByIdsForCommerce($commerce);

        $commerce = Commerce::getById($commerce, ['code_rke', 'code_uuid']);
        $commerce = $commerce->code_rke ?? $commerce->code_uuid;

        return view(
            'modules.commerce.games.bingo.cartons.awarwed.my-winners',
            compact(
                'title',
                'wallet',
                'name',
                'dateFilter',
                'commerce',
                'winners',
                'paymentsOtherCommerces',
                'countWinnersOtherCommerces',
                'countWinnersByCommerce',
                'coins'
            )
        );
    }

    public function getBingoWinnersByOtherCommerces($dateFilter = 'all')
    {
        $title = 'Últimos Ganadores';
        $name = 'notify-payment-win-ctns-off';

        $commerce = $this->commerce()->id;

        app(HandleBingoRooms::class)->checkRoomClosure();

        // Obtener los datos de los ganadores
        $winners = $this->getWinnersData($commerce, $dateFilter, true);

        // Contar los ganadores pendientes de pago para otros comercios
        $countWinnersOtherCommerces = BingoPrizeCtns::getPendingPaymentsCountExcludingCommerce($commerce);

        // Contar los ganadores pendientes de pago para el propio comercio
        $countWinnersByCommerce = BingoPrizeCtns::getPendingPaymentsCountByCommerce($commerce);

        // Obtener las monedas asociadas al comercio
        $coins = $this->getByIdsForCommerce($commerce);

        return view(
            'modules.commerce.games.bingo.cartons.awarwed.other-winners',
            compact(
                'title',
                'name',
                'commerce',
                'dateFilter',
                'winners',
                'countWinnersOtherCommerces',
                'countWinnersByCommerce',
                'coins'
            )
        );
    }

    private function getWinnersData($commerce = '', $dateFilter = "today", $others = false)
    {
        $winnersQuery = BingoPrizeCtns::with([
            'Room',
            'Commerce',
            'Coin',
            'PlayerLink.Country',
            'Payment',
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

        if (!$others) {
            $winnersQuery->where('commerce', $commerce);
        } else {
            $winnersQuery->whereNot('commerce', $commerce);
        }

        $winnersQuery = $this->applyDateFilter($dateFilter, $winnersQuery);

        return $winnersQuery->where('active', 'Y')->orderBy('prize_type')->get();

        //$winnersJackpot = $winners->where('prize_type', 1)->count();
        //$winnersSerials = $winners->where('prize_type', 2)->count();
    }

    private function getByIdsForCommerce($commerce)
    {
        // Lógica para obtener las monedas asociadas al comercio
        $coinsCommerce =  CommerceCountriesCoins::with('Coin', 'PlayerLink.PlayerGame')
            ->whereHas('PlayerLink', function ($query) use ($commerce) {
                $query->whereColumn('coin', 'commerces_countries_coins.coin')
                    ->where('commerce', $commerce);
            })->whereHas('PlayerLink.PlayerGame', function ($query) {
                $query->where('winner', '>', 0);
            })->where('status', 1)->orderBy('country')->pluck('coin');

        return Coin::getCoinsById($coinsCommerce);
    }

    public function getPaymentCommerceDetails($commerce, $coin)
    {
        $commerceID = decrypt($commerce);
        $coin = decrypt($coin);

        $commerce = Commerce::getById($commerceID, ['name', 'user']);
        // Obtener detalles de pago por comercio usando el ID del usuario autenticado y la moneda desencriptada
        $paymentDetails = $this->getPaymentDetailsByUserId($commerce->user, $coin);

        if (!$paymentDetails) return response()->json([]);

        $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($commerceID, $coin);
        $commerce = $commerce->name;

        return response()->json(compact('commerce', 'paymentDetails', 'exchangeRate'));
    }

    public function ctnsSold($coin, $dateFilter = "today")
    {
        $title = 'Cartones Vendidos';

        $commerce = $this->commerce('id')->id;

        $verifyCoin = $this->verifyExistenceOfSales($coin, ['type' => 'commerce', 'id' => $commerce]);

        $existSale = $this->existsCoin(BingoCtnsLot::where('commerce', $commerce), $coin, $dateFilter);

        if (!$existSale && $dateFilter !== 'all') $verifyCoin['return'] = true;

        if ($verifyCoin['return']) {
            return redirect()->route(
                'commerce.ctnsSold',
                ['coin' => encrypt($verifyCoin['coin']), 'dateFilter' => 'all']
            );
        }

        $coin = $verifyCoin['coin'];

        $coinSym = Coin::getValue($coin, 'symbol');

        $ctnsSoldQuery = BingoCtnsLot::with(['Room', 'Coin', 'Payment', 'Seller'])
            ->whereHas('Room', function ($query) use ($commerce) {
                $query->where('commerce', $commerce)->whereIn('status', [2, 3, 4]);
            });

        $ctnsSoldQuery = $this->applyDateFilter($dateFilter, $ctnsSoldQuery);

        $ctnsSold = $ctnsSoldQuery->where('coin', $coin)->where('commerce', $commerce)
            ->whereHas('Seller')->orderBy('status')->orderByDesc('id')->get();

        $coins = $this->getCommerceCoins($commerce, $dateFilter, ['CtnsLot', 'Coin']);

        $routeWithCoin = [
            'route' => 'commerce.ctnsSold',
            'parameters' => ['dateFilter' => $dateFilter]
        ];

        return view(
            'modules.commerce.games.bingo.cartons.ctns-sold',
            compact('title', 'ctnsSold', 'routeWithCoin', 'dateFilter', 'coin', 'coinSym', 'coins')
        );
    }

    public function ctnsLotPayment(Request $request)
    {
        DB::beginTransaction();

        try {
            $lot = decrypt($request->lot);
            $lotCtn = BingoCtnsLot::where('id', $lot)->firstOrFail();
            $lotCtn->update(['status' => 2]);

            DB::commit();

            return redirect()->back()->with('message', 'Pago confirmado con éxito');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al confirmar el pago: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Hubo un error al confirmar el pago.']);
        }
    }

    public function notifyPaymentPrizeWinningCtn(Request $request)
    {
        $ctnId = Crypt::decrypt($request->ctn);

        $validator = Validator::make(['ctnId' => $ctnId], [
            'ctnId' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $ctn = BingoCtns::select('room', 'commerce', 'player_link', 'seller', 'coin', 'lot')->findOrFail($ctnId);

        if (!$ctn) return redirect()->back();

        $prizeCtn = BingoPrizeCtns::getInfoByCtn($ctnId, ['id', 'commerce', 'prize_type', 'amount']);

        $prizeCtnPayment = BingoPrizeCtnsPayment::where('prize_ctn', $prizeCtn->id)->where('operation', 1)->exists();

        if ($prizeCtnPayment) return redirect()->back();

        DB::beginTransaction();

        try {
            $operation = ($prizeCtn->commerce == $this->commerce()->id) ? 1 : 3;
            $amount = ($operation == 1) ? $prizeCtn->amount : $request->amount;

            // Crear el registro en la base de datos primero
            $payment = $this->registerPrizeCartonPayment(
                $prizeCtn->id,
                $operation,
                $ctn->room,
                $ctn->commerce,
                $ctn->seller,
                $ctn->player_link,
                Auth::id(),
                $prizeCtn->prize_type, // Tipo de pago [2] Cartón premiado
                $ctn->lot,
                $ctnId,
                $ctn->coin,
                $amount
            );

            $filePayment = $request->file('payment_proof');
            $newFileName = FileController::saveImage($filePayment, 'bingo/payments/prize-ctns');

            if (!$newFileName) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Error al guardar el archivo de pago.'], 500);
            }

            $payment->update(['payment_proof' => $newFileName]);

            // PAGO [CTN WIN] ENVIADO DE COMERCIO GANADOR A VENDEDOR
            BingoPrizeCtns::where('ctn', $ctnId)->update(['status' => 1]);

            DB::commit();

            return redirect()->back()->with('message', 'Pago notificado con éxito');
        } catch (DecryptException $e) {
            return response()->json(['error' => 'No se pudo descifrar el ID del cartón.'], 400);
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($newFileName)) {
                Storage::delete('bingo/payments/prize-ctns/' . $newFileName);
            }

            Log::error('Error al crear el registro y guardar la imagen: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Hubo un error al subir la imagen.'], 500);
        }
    }
}
