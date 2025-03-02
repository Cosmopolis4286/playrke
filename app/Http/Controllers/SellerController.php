<?php

namespace App\Http\Controllers;

use App\Models\BingoRoom;
use App\Models\BingoRoomUser;
use App\Models\BingoCtns;
use App\Models\BingoCtnsLot;
use App\Models\PlayerLink;
use App\Models\Seller;
use App\Models\DocumentType;
use App\Traits\RouteRKE;
use App\Traits\EncryptRKE;
use App\Traits\BingoTrait;
use App\Traits\PlayerLinkTrait;
use App\Traits\UserVal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BingoCreator;
use App\Models\BingoCtnPrice;
use App\Models\BingoCtnsPayment;
use App\Models\BingoPrizeCtns;
use App\Models\BingoPrizeCtnsPayment;
use App\Models\Coin;
use App\Models\Commerce;
use App\Models\CommerceCountriesCoins;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\PhoneNumbers;
use App\Models\SellerCountriesCoins;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;
use App\Services\Game\Bingo\HandleBingoRooms;
use App\Traits\Filters;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SellerController extends Controller
{
    use EncryptRKE, BingoTrait, PlayerLinkTrait, RouteRKE, UserVal, Filters;

    private function validateRequest($request, $type = 1, $id = '')
    {
        // Definir reglas de validación.
        $rules = [
            'username' => ['required', 'string', 'max:20', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:40', 'unique:users,email'],
            'country' => ['required', 'numeric', 'exists:countries,id'],
            'coin' => ['required', 'numeric', 'exists:coins,id'],
            'name' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:20', 'unique:sellers,phone'],
            'commission' => ['required', 'numeric', 'between:0,25'],
            'other_coins.*' => ['exists:coins,id'],
        ];

        // Ajustar reglas de validación si es una actualización.
        if ($type == 2) {
            $seller = Seller::findOrFail(decrypt($id));
            $rules['username'] = ['required', 'string', 'max:20', 'unique:users,username,' . $seller->user];
            $rules['email'] = ['required', 'string', 'email', 'max:40', 'unique:users,email,' . $seller->user];
            $rules['phone'] = ['required', 'string', 'max:20', 'unique:sellers,phone,' . $seller->id];
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
            if ($type > 1) Session::flash('encryptedId', decrypt($id));

            return $validator; // Devuelve el validador en caso de falla
        }

        return $validator; // Devuelve el validador en caso de éxito
    }

    private function insertOtherCoins($seller, $otherCoins)
    {
        if (empty($otherCoins)) {
            DB::beginTransaction();
            try {
                $sellerCoin = Seller::getValueById($seller, 'coin');
                SellerCountriesCoins::where('seller', $seller)->whereNot('coin', $sellerCoin)->update(['status' => 0]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al inhabilitar todas las monedas para el vendedor: ' . $e->getMessage());
            }
        } else if (!empty($otherCoins) && is_array($otherCoins)) {
            // Obtener las monedas seleccionadas y prepararlas para la inserción
            $otherCoins = Coin::getCoinsById($otherCoins, ['id', 'country']);
            $timestamp = now();

            $coinsToCreate = [];

            foreach ($otherCoins as $coin) {
                $coinsToCreate[] = [
                    'seller' => $seller,
                    'country' => $coin->country,
                    'coin' => $coin->id,
                    'status' => 1,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];
            }

            // Manejo de transacción para insertar las nuevas monedas
            DB::transaction(function () use ($seller, $coinsToCreate) {
                try {
                    foreach ($coinsToCreate as $coinData) {
                        // Insertar la moneda si no existe para ese vendedor
                        SellerCountriesCoins::firstOrCreate(
                            ['seller' => $seller, 'coin' => $coinData['coin']],
                            $coinData
                        );
                    }

                    // Habilitar solo las monedas seleccionadas
                    SellerCountriesCoins::where('seller', $seller)
                        ->whereIn('coin', array_column($coinsToCreate, 'coin'))->update(['status' => 1]);
                } catch (\Exception $e) {
                    // Captura de cualquier otra excepción
                    DB::rollBack();
                    Log::error('Error general al insertar monedas para el vendedor: ' . $e->getMessage());
                    throw $e;
                }
            });
        }
    }

    public function create(Request $request)
    {
        if (!in_array($this->user(['user_type'])->user_type, [2, 3])) {
            return redirect()->back()->withErrors(['error' => 'No tienes permiso para realizar esta acción.']);
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
                'user_type' => UserType::getValueByPrefix('seller', 'id'), // Vendedor
                'email' => $request->email,
                'password' => Hash::make($newPassw),
                'country' => $request->country,
                'email_verified_at' => Carbon::now(),
                'referral_code' => User::getUniqueReferralCode(),
            ])->id;

            // Crear un nuevo Wallet para el usuario
            Wallet::create(['coin' => $request->coin, 'user' => $newUser]);

            $phoneNumber = $request->phone;
            PhoneNumbers::create([
                'user' => $newUser,
                'country' => $request->country,
                'phone_number' => $phoneNumber
            ]);

            $seller = Seller::create([
                'name' => $request->name,
                'phone' => $phoneNumber,
                'coin' => $request->coin,
                'country' => $request->country,
                'commerce' => Commerce::getValueByUserId(Auth::id(), 'id'),
                'commn' => $request->commission,
                'status' => 1,
                'user' => $newUser
            ]);

            // Registar moneda Principal
            SellerCountriesCoins::create([
                'seller' => $seller->id,
                'country' => $seller->country,
                'coin' => $seller->coin,
                'status' => 1
            ]);

            $otherCoins = $request->other_coins;
            $this->insertOtherCoins($seller->id, $otherCoins);

            DB::commit();

            // Mensajes flash con tiempo de expiración personalizado
            Session::flash('message', 'Vendedor ' . $request->name . ' registrado con éxito');
            Session::flash('message-passw', $newPassw);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error general: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Hubo un error al procesar la solicitud.']);
        }

        return redirect()->back();
    }

    public function getInfo($id)
    {
        $id = decrypt($id);

        $seller = Seller::getById($id, ['name', 'commerce', 'commn', 'country', 'phone']);
        $otherCoins = SellerCountriesCoins::getAvailableCoins($id);
        $user = User::getById(Seller::getValueById($id, 'user'), ['username', 'email']);

        $data = [
            'seller' => $seller,
            'user' => $user,
            'other_coins' => $otherCoins
        ];

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        if (!in_array($this->user(['user_type'])->user_type, [2, 3])) {
            return redirect()->back()->withErrors(['error' => 'No tienes permiso para realizar esta acción.']);
        }

        DB::beginTransaction();

        try {
            // Decryptar el ID y encontrar el vendedor
            $seller = Seller::findOrFail(decrypt($id));

            if ($seller) {
                $validator = $this->validateRequest($request, 2, $id);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator)->withInput();
                }

                // Verificar si la moneda principal ha cambiado
                $coinChange = $seller->coin !== $request->coin;

                $phoneNumber = $request->phone;

                if ($phoneNumber <> $seller->phone) {
                    PhoneNumbers::where('user', $seller->user)->update([
                        'country' => $request->country,
                        'phone_number' => $phoneNumber
                    ]);
                }

                // Actualizar los campos del vendedor
                $seller->update([
                    'name' => $request->name,
                    'country' => $request->country,
                    'coin' => $request->coin,
                    'phone' => $phoneNumber,
                    'commn' => $request->commission
                ]);

                if ($coinChange) {
                    // Actualizar moneda Principal
                    SellerCountriesCoins::where('seller', $seller->id)
                        ->where('coin', $seller->coin)->update([
                            'country' => $seller->country,
                            'coin' => $seller->coin
                        ]);
                }

                $otherCoins = $request->other_coins;
                $this->insertOtherCoins($seller->id, $otherCoins);

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
        $seller = Seller::findOrFail(decrypt($id));
        $seller->delete();
        Session::flash('message', 'El Vendedor/Comercio <b>' . $seller->name . '</b> fue eliminado');
        return redirect()->back();
    }

    public function changeStatus($id)
    {
        $id = decrypt($id);
        $sellerStatus = (int)!Seller::getValueById($id, 'status');

        Seller::where('id', $id)->update(['status' => $sellerStatus]);

        Session::flash('message', 'Status actualizado');
        return redirect()->back()->with('type', 'success');
    }

    public function changePassword($id)
    {
        $newPassw = $this->valEncrypt();
        $seller = Seller::findOrFail(decrypt($id));

        User::where('id', $seller->user)->update(['password' => Hash::make($newPassw)]);

        Session::flash('message', 'La nueva contraseña del vendedor es: ' . ' <b>' . $newPassw . '</b>');
        Session::flash('message-passw', $newPassw);
        return redirect()->back();
    }

    public function cartonGenerate()
    {
        $documentType = DocumentType::where('country', 234)->get();

        $seller = $this->seller(['commerce', 'country', 'commn', 'status']);

        $creatorUser = Commerce::getValueById($seller->commerce, 'user');

        (new HandleBingoRooms())->checkRoomClosure();

        $rooms = BingoRoom::with([
            'Config' => function ($query) {
                $query->select('room', 'game_mode', 'unix_date');
            }
        ])->select('id', 'encrypted', 'room_type')
            ->where('status', 2)
            ->where(function ($query) use ($creatorUser) {
                $query->where('creator_user', $creatorUser)
                    ->orWhere('room_type', 'PV');
            })
            ->whereHas('Config', function ($query) {
                $query->where('game_mode', 2)
                    ->whereNotNull('room');
            })
            ->orderByRaw("(SELECT unix_date FROM bingo_rooms_config WHERE bingo_rooms_config.room = bingo_rooms.id LIMIT 1)")
            ->get();

        return view('modules/seller/games/bingo/cartons/generate')->with([
            'title' => 'Generar Cartón',
            'documentType' => $documentType,
            'status' => $seller->status,
            'commn' => $seller->commn,
            'country' => $seller->country,
            'rooms' => $rooms
        ]);
    }

    public function getAvailableCountriesBySeller()
    {
        $seller = $this->seller(['id', 'commerce']);

        $coinsSeller = SellerCountriesCoins::getAvailableCoins($seller->id);

        $commerceCoins = CommerceExchangeRate::getCoinsByCommerce($seller->commerce, 1);

        $coinsAvailable = array_intersect($commerceCoins, $coinsSeller);

        if (in_array('1', $coinsAvailable)) {
            $coinsAvailable = array_diff($coinsAvailable, ['1']);
            $USDT = true;
        }

        $countriesAvailable = Coin::getCountriesByCoinInArray($coinsAvailable);

        $countries = Country::getCountries($countriesAvailable, ['id', 'name']);

        return ['countries' => $countries, 'USDT' => isset($USDT)];
    }

    public function getCtnPrice($roomID)
    {
        $room = BingoRoom::with('Config:room,jackpot_type,ctn_price')
            ->select('id', 'encrypted', 'jackpot')
            ->where('encrypted', $roomID)->first();

        if (!$room) {
            return response()->json(['type' => 'info', 'message' => 'Sala no encontrada']);
        }

        $countriesCoins = $this->getAvailableCountriesBySeller();

        return response()->json([
            'jackpotType' => $room->Config->jackpot_type,
            'jackpot' => $room->jackpot,
            'coin' => 'USDT',
            'price' => $room->Config->ctn_price,
            'countries' => $countriesCoins['countries'],
            'USDT' => $countriesCoins['USDT']
        ]);
    }

    public function getCtnPriceCoin($room, $country)
    {
        // Obtener moneda según el país
        $coin = Coin::getCoinByCountry($country, ['id', 'symbol']);
        if (!$coin) {
            return response()->json(['error' => 'Moneda no encontrada'], 404);
        }

        // Obtener comercio asociado al usuario autenticado
        $commerce = Seller::getValueByUserId(Auth::id(), 'commerce');
        if (!$commerce) {
            return response()->json(['error' => 'Comercio no encontrado'], 404);
        }

        // Obtener tasa de cambio
        $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($commerce, $coin->id);

        $roomID = BingoRoom::getValueByEncrypted($room, 'id');
        $creator = ['type' => 'commerce', 'id' => $commerce];

        $setPrice = false;
        $value = $exchangeRate;

        //Obtener $bingoCreatorID
        $bingoCreatorID = BingoCreator::getValueByCreatorRoom($creator, $roomID, 'id');

        // Intentar obtener el precio configurado
        $ctnPrice = BingoCtnPrice::getValueCtnPrice($roomID, $coin->id, $bingoCreatorID);

        if ($ctnPrice) {
            $setPrice = true;
            $value = $ctnPrice;
        }

        // Construir y devolver la respuesta
        return response()->json([
            'sym' => $coin->symbol,
            'exchangeRate' => $exchangeRate,
            'setPrice' => $setPrice,
            'value' => $value,
        ]);
    }

    public function getPlayer(Request $request)
    {
        switch ($request->game) {
            case 'BINGO':
                $room = BingoRoom::getByEncrypted($request->room, ['id', 'room_type']);

                $player = PlayerLink::select('id', 'name', 'lastname', 'country', 'phone', 'link')
                    ->where('phone', $request->player_info)->where('game', 1)->where('room', $room->id)->first();

                if (!$player)  return response()->json(['response' => false]);

                $route = $this->getGameLinkByPlayer(false, $player->link, $room->room_type);
                $ctnsPlayer = BingoCtns::where(['player_link' => $player->id, 'room' => $room->id])->count();

                return response()->json([
                    'response' => true,
                    'infoPlayer' => $player,
                    'ctnsLink' => [$ctnsPlayer, $player->link, $route]
                ]);

                break;

            default:
                break;
        }
    }

    public function getPaymentCommerceDetails($coin)
    {
        try {
            $user = Commerce::getValueById($this->seller('commerce')->commerce, 'user');

            // Obtener detalles de pago por comercio usando el ID del usuario autenticado y la moneda decriptada
            $data = $this->getPaymentDetailsByUserId($user, decrypt($coin));

            // Verificar si se obtuvieron datos y retornar una respuesta JSON adecuada
            if ($data) {
                return response()->json($data);
            } else {
                Session::flash('msg-error', 'Datos de pago no encontrados');
                return response()->json(['error' => 'Datos de pago no encontrados.'], 404);
            }
        } catch (\Exception $e) {
            // Manejar cualquier excepción que ocurra y retornar un error del servidor
            return response()->json(['error' => 'Ha ocurrido un error al procesar la solicitud.'], 500);
        }
    }

    private function getSellerCoins($seller, $dateFilter = "today", $relation = [], $where = null)
    {
        // Inicializamos la consulta con la relación principal
        $query = CommerceCountriesCoins::with($relation[0])
            ->whereHas($relation[0], function ($subquery) use ($seller, $relation, $dateFilter, $where) {
                $subquery->whereColumn('coin', 'commerces_countries_coins.coin')
                    ->where('seller', $seller);

                // Aplicar condiciones adicionales desde $where
                if ($where && $where->isNotEmpty()) {
                    foreach ($where as $item) {
                        $subquery->where($item->column, $item->value);
                    }
                }

                // Cargar una relación adicional si está definida
                if (isset($relation[1])) {
                    $subquery->with($relation[1]);
                }

                // Aplicar filtro de fecha
                $this->applyDateFilter($dateFilter, $subquery);
            });

        // Obtener las monedas activas y ordenarlas por país
        $coins = $query->where('status', 1)->orderBy('country')->pluck('coin');

        // Devolver las monedas usando un método estático de la clase Coin
        return Coin::getCoinsById($coins);
    }

    public function ctnsSold($coin = '', $dateFilter = "today")
    {   //Cartones Vendidos Salas Actuales Status 2 o 3
        $seller = $this->seller(['id', 'commerce']);

        $user = ['type' => 'seller', 'id' => $seller->id];

        $verifyCoin = $this->verifyExistenceOfSales($user, $dateFilter, $coin);

        if (isset($verifyCoin['return'])) {
            return redirect()->route(
                'seller.ctnsSold',
                ['coin' => encrypt($verifyCoin['coin']), 'dateFilter' => 'all']
            );
        }

        $coin = Coin::getById($verifyCoin);

        $ctnsSoldQuery = BingoCtnsLot::with([
            'Payment',
            'PlayerLink',
            'Room',
            'ComSellerRoom' => function ($query) use ($seller) {
                $query->where('seller', $seller->id);
            }
        ])->whereHas('Room', function ($query) use ($seller) {
            $query->where('commerce', $seller->commerce)->where('status', '>=', 2);
        })->whereHas('ComSellerRoom', function ($query) {
            $query->whereNotNull('room');
        });

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

        $coins = $this->getSellerCoins($seller->id, $dateFilter, ['CtnsLot']);

        $routeWithCoin = [
            'route' => 'seller.ctnsSold',
            'parameters' => ['dateFilter' => $dateFilter]
        ];

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
    }

    public function ctnsAwarded($coin = '', $dateFilter = "today")
    {
        $title = 'Ganadores';
        $name = 'notify-payment-win-ctns-off';

        $seller = $this->seller(['id', 'commerce']);

        $user = ['type' => 'seller', 'id' => $seller->id];

        $verifyCoin = $this->verifyExistenceOfSales($user, $dateFilter, $coin);

        if (isset($verifyCoin['return'])) {
            return redirect()->route(
                'seller.ctnsAwarded',
                ['coin' => encrypt($verifyCoin['coin']), 'dateFilter' => 'all']
            );
        }

        $coin = $verifyCoin;

        app(HandleBingoRooms::class)->checkRoomClosure();

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

        $coins = $this->getSellerCoins($seller->id, $dateFilter, ['PlayerLink', 'PrizeCtns']);

        $routeWithCoin = [
            'route' => 'seller.ctnsAwarded',
            'parameters' => ['dateFilter' => $dateFilter]
        ];

        $coinInfo = Coin::getById($coin, ['id', 'symbol', 'iso_code']);
        $coinSym = $coinInfo->symbol;
        $coinISOCode = $coinInfo->iso_code;

        return view(
            'modules.seller.games.bingo.cartons.awarwed',
            compact('title', 'name', 'commerce', 'winners', 'routeWithCoin', 'dateFilter', 'coin', 'coinSym', 'coinISOCode', 'coins')
        );
    }

    public function getWinnerInfoForCtn($ctnId)
    {
        $ctnId = decrypt($ctnId);
        $idPlayer = BingoPrizeCtns::getValueById($ctnId, 'player_link');
        $player = PlayerLink::getById($idPlayer, ['name', 'lastname', 'phone']);

        return json_encode(array(
            'name' => $player->name,
            'lastname' => $player->lastname,
            'phone' => $player->phone
        ));
    }

    public function notifyPaymentPrizeWinningCtn(Request $request)
    {
        $ctnId = decrypt($request->ctn);

        $validator = Validator::make(['ctnId' => $ctnId], [
            'ctnId' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $ctn = BingoCtns::select('room', 'commerce', 'player_link', 'seller', 'coin', 'lot')->findOrFail($ctnId);

        if (!$ctn) return redirect()->back();

        $prizeCtn = BingoPrizeCtns::getInfoByCtn($ctnId, ['id', 'commerce', 'prize_type', 'amount']);

        $prizeCtnPayment = BingoPrizeCtnsPayment::where('prize_ctn', $prizeCtn->id)->where('operation', 2)->exists();

        if ($prizeCtnPayment) return redirect()->back();

        DB::beginTransaction();

        try {
            // Crear el registro en la base de datos primero
            $payment = $this->registerPrizeCartonPayment(
                $prizeCtn->id,
                2,
                $ctn->room,
                $ctn->commerce,
                $ctn->seller,
                $ctn->player_link,
                Auth::id(),
                $prizeCtn->prize_type, // Tipo de pago [2] Cartón premiado
                $ctn->lot,
                $ctnId,
                $ctn->coin,
                $prizeCtn->amount
            );

            $filePayment = $request->file('payment_proof');
            $newFileName = FileController::saveImage($filePayment, 'bingo/payments/prize-ctns');

            if (!$newFileName) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Error al guardar el archivo de pago.'], 500);
            }

            // Actualiza el registro con el nombre del archivo
            $payment->update(['payment_proof' => $newFileName]);

            // Actualiza status (carton ganador pagado)
            BingoPrizeCtns::where('ctn', $ctnId)->update(['status' => 2]);

            // PAGO [CTN WIN] ENVIADO DE VENDEDOR A GANADOR
            $success = BingoRoomUser::where('player_link', $ctn->player_link)->update(['winner' => 2]);

            if ($success) {
                $this->AllWinCtnPaid($ctn->room);
                Session::flash('message', 'Pago notificado con éxito');
                DB::commit();
                return redirect()->back();
            } else {
                DB::rollBack();
                return response()->json(['error' => 'Failed to update CTN payment status'], 500);
            }

            Session::flash('message', 'Pago notificado con éxito');
        } catch (DecryptException $e) {
            return response()->json(['error' => 'No se pudo descifrar el ID del cartón.'], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            // Eliminar el archivo subido en caso de error
            if (isset($newFileName)) {
                Storage::delete('bingo/payments/prize-ctns/' . $newFileName);
            }
            Log::error('Error al subir y guardar la imagen: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Hubo un error al subir la imagen.'], 500);
        }
    }

    public function getPlayerInfo($playerLink) // Obtención de la información jugador
    {
        $player = PlayerLink::where('link', $playerLink)
            ->select(['type_id', 'identification', 'phone'])->first();
        if ($player) return json_encode($player);
    }

    public function reportSalesRooms($coin = '', $dateFilter = "today") // Muestra el reporte por salas 
    {
        $title = 'Reporte de Ventas';

        $seller = $this->seller(['id', 'commerce']);

        $user = ['type' => 'seller', 'id' => $seller->id];

        $verifyCoin = $this->verifyExistenceOfSales($user, $dateFilter, $coin);

        if (isset($verifyCoin['return'])) {
            return redirect()->route(
                'seller.reportSalesRooms',
                ['coin' => encrypt($verifyCoin['coin']), 'dateFilter' => 'all']
            );
        }

        $coin = $verifyCoin;

        $roomsQuery = BingoRoom::with([
            'Config',
            'CtnsLot' => function ($query) use ($seller, $coin) {
                $query->where('seller', $seller->id)->where('coin', $coin)->with('Coin');
            }
        ])->whereHas('CtnsLot', function ($query) use ($seller, $coin) {
            $query->where('seller', $seller->id)->where('coin', $coin);
        });

        $roomsQuery = $this->applyDateFilter($dateFilter, $roomsQuery, 'date');

        $rooms = $roomsQuery->where('commerce', $seller->commerce)
            ->get(['id', 'encrypted', 'time', 'date', 'jackpot', 'ctn_price']);

        if (!empty($room));

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

        $coinInfo = Coin::getById($coin, ['id', 'symbol', 'long_name']);

        $coinSym = $coinInfo->symbol;
        $coinName = $coinInfo->long_name;

        $coins = $this->getSellerCoins($seller->id, $dateFilter, ['CtnsLot']);

        $routeWithCoin = [
            'route' => 'seller.reportSalesRooms',
            'parameters' => ['dateFilter' => $dateFilter]
        ];

        return view(
            'modules.seller.games.bingo.report.rooms',
            compact('title', 'rooms', 'roomsData', 'routeWithCoin', 'dateFilter', 'coins', 'coin', 'coinName', 'coinSym', 'totalSale', 'totalComision')
        );
    }

    public function reportSalesByRoom($room, $coin) // Muestra el reporte de una sala
    {
        $room = BingoRoom::getByEncrypted($room, ['id', 'encrypted']);

        if (!$room) return redirect()->back();

        $generateUniqueCodeed = $room->encrypted;

        $seller = $this->seller(['id', 'commerce']);
        $user = ['type' => 'seller', 'id' => $seller->id];

        $verifyCoin = $this->verifyExistenceOfSales($user, '', $coin, $room->id);

        if (isset($verifyCoin['return'])) {
            return redirect()->back();
        }

        $coin = $verifyCoin;

        $playersRoom = PlayerLink::with(['Coin', 'CtnsLot'])
            ->where(['seller' => $seller->id, 'room' => $room->id, 'coin' => $coin])
            ->orderByDesc('id')->get(['id', 'link', 'coin', 'name', 'lastname', 'phone']);

        if (!count($playersRoom)) return redirect()->back();

        $title = 'Reporte de Sala';
        $cardTitle = "<span> Reporte de Sala </span> <span class='badge badge-secondary'>{$room->encrypted}</span>";

        // Realizar los cálculos de CTNs 
        $totalCtnsPurchased = $playersRoom->pluck('CtnsLot')->flatten()->sum('sale_amount');

        $totalCtnsByRoom = $playersRoom->flatMap->CtnsLot->sum('ctns');

        $where = collect([(object) ['column' => 'room', 'value' => $room->id]]);

        $coins = $this->getSellerCoins($seller->id, '', ['CtnsLot'], $where);

        $coinInfo = Coin::getById($coin, ['symbol', 'long_name']);

        $coinName = $coinInfo->long_name;
        $coinSym = $coinInfo->symbol;

        $commerce = Commerce::getValueById($seller->commerce, 'code_uuid');

        $routeWithCoin = [
            'route' => 'seller.reportSalesByRoom',
            'parameters' => ['room' => $room->encrypted]
        ];

        return view(
            'modules.seller.games.bingo.report.by-room',
            compact(
                'title',
                'cardTitle',
                'commerce',
                'generateUniqueCodeed',
                'playersRoom',
                'routeWithCoin',
                'coins',
                'coin',
                'coinSym',
                'coinName',
                'totalCtnsPurchased',
                'totalCtnsByRoom'
            )
        );
    }

    // public function reportSalesByRoomForPlayer($room, $player) // Muestra el reporte por Jugador y sus cartones 
    // {
    //     $seller = $this->seller();
    //     $coin = decrypt($coin);
    //     $title = 'Reporte de Sala';

    //     $room = BingoRoom::where('encrypted', $room)->value('id');

    //     if (!$room) return redirect()->back();

    //     $playersRoom = PlayerLink::with(['Coin', 'CtnsLot'])->whereHas('CtnsLot')
    //         ->where(['seller' => $seller->id, 'room' => $room, 'coin' => $coin])->orderByDesc('id')
    //         ->get(['id', 'link', 'coin', 'name', 'lastname', 'phone']);

    //     if (!count($playersRoom)) return redirect()->back();

    //     $coinsCommerce = CommerceCountriesCoins::with('CtnsLot')
    //         ->whereHas('CtnsLot', function ($query) use ($seller, $room) {
    //             $query->whereColumn('coin', 'commerces_countries_coins.coin')
    //                 ->where('commerce', $seller->commerce)->where('room', $room);
    //         })->where('status', 1)->orderBy('country')->pluck('coin');

    //     $coins = Coin::getCoinsById($coinsCommerce);

    //     $coin = Coin::getById($coin);

    //     return view(
    //         'modules.seller.games.bingo.report.by-room',
    //         compact('title', 'room', 'playersRoom', 'coin', 'coin', 'coins')
    //     );
    // }

    public function ctnsLotPayment(Request $request)
    {
        DB::beginTransaction();

        try {
            $seller = $this->seller()->id;

            // Buscar el lote
            $lotCtn = BingoCtnsLot::where('id', $request->lot)->where('seller', $seller)->firstOrFail();

            $notifyAllCtns = $request->notify_all_ctns;

            if (!$lotCtn && !$notifyAllCtns) {
                return redirect()->back();
            }

            $filePayment = $request->file('payment_proof');
            $newFileName = FileController::saveImage($filePayment, 'bingo/payments/all-ctns');

            if (!$newFileName) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Error al guardar el archivo de pago.'], 500);
            }

            if ($notifyAllCtns) {
                $notifyCtns = $this->notifyAllPendingCtns($seller, $newFileName);
            } else {
                $notifyCtns = $this->notifySpecificCtn($lotCtn, $newFileName);
            }

            if (!$notifyCtns) DB::rollBack();

            DB::commit();

            Session::flash('message', 'Pago notificado con éxito');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($newFileName)) {
                Storage::delete('bingo/payments/all-ctns' . $newFileName);
            }

            return redirect()->back()->with('error', 'Hubo un error al notificar el pago: ' . $e->getMessage());
        }
    }

    private function notifyAllPendingCtns($seller, $newFileName)
    {
        $allPendingCtns = BingoCtns::with('Lot')
            ->whereHas('Lot', function ($query) use ($seller) {
                $query->where(['seller' => $seller, 'status' => 0]);
            })->where('seller', $seller)->get(['id', 'room', 'commerce', 'player_link', 'coin', 'lot']);

        try {
            $paymentToInsert = [];

            foreach ($allPendingCtns as $value) {
                $ctnPrice = $value->Lot->sale_amount / $value->Lot->ctns;
                $timestamp = now();

                $paymentToInsert[] = [
                    'operation' => 1,
                    'room' => $value->room,
                    'commerce' => $value->commerce,
                    'seller' => $seller,
                    'player_link' => $value->player_link,
                    'payment_type' => 1,
                    'lot' => $value->lot,
                    'coin' => $value->coin,
                    'amount' => $ctnPrice,
                    'payment_proof' => $newFileName,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];
            }
            BingoCtnsPayment::insert($paymentToInsert);

            BingoCtnsLot::where('seller', $seller)->where('status', 0)->update(['status' => 1]);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    private function notifySpecificCtn($lotCtn, $newFileName)
    {
        try {
            $this->registerCartonPayment(
                1, // Tipo Operations
                $lotCtn->room,
                $lotCtn->commerce,
                $lotCtn->seller,
                $lotCtn->player_link,
                1, // Tipo de Pago
                $lotCtn->id,
                $lotCtn->coin,
                $lotCtn->sale_amount,
                $newFileName
            );

            BingoCtnsLot::where('id', $lotCtn->id)->where('seller', $lotCtn->seller)
                ->where('status', 0)->update(['status' => 1]);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
