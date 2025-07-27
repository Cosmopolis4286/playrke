<?php

namespace App\Http\Controllers;

use App\Models\BingoRoom;
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
use App\Models\Coin;
use App\Models\Commerce;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\PhoneNumbers;
use App\Models\SellerCountriesCoins;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;
use App\Services\Game\Bingo\HandleBingoRooms;
use App\Services\RKE\HandleExchangeRates;
use App\Traits\Filters;
use Carbon\Carbon;
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
            'time_zone' => ['required', 'numeric', 'exists:time_zones,id'],
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
                'time_zone' => $request->time_zone,
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
                'level' => '1',
                'creator_user' => $newUser,
                'status' => 1
            ]);

            $creatorUser = [
                'model' => 'Seller',
                'id' => $seller->id,
                'userId' => $newUser
            ];

            $otherCoins = $request->other_coins;

            if ($otherCoins) {
                // Insertar otras monedas
                $this->insertOtherCoins($creatorUser, $otherCoins);
            }

            DB::commit();

            Session::flash('message', 'Registro exitoso');
            Session::flash('message-passw', $newPassw);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear Seller: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error al procesar la solicitud']);
        }

        return redirect()->back();
    }

    public function getInfo($id)
    {
        $id = decrypt($id);

        $seller = Seller::getById($id, ['name', 'commerce', 'commn', 'country', 'phone']);
        $otherCoins = SellerCountriesCoins::getAvailableCoins($id);
        $user = User::getById(Seller::getValueById($id, 'user'), ['username', 'email', 'seller']);

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

                $userID = $seller->user;
                $userTimeZone = User::getValueById($userID, 'time_zone');

                if ($userTimeZone <> $request->time_zone) {
                    User::where('id', $userID)->update(['time_zone' => $request->time_zone]);
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

                $creatorUser = [
                    'model' => 'Seller',
                    'id' => $seller->id,
                    'userId' => $seller->user
                ];

                $otherCoins = $request->other_coins;

                if ($otherCoins) {
                    // Insertar otras monedas
                    $this->insertOtherCoins($creatorUser, $otherCoins);
                }

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

        app(HandleBingoRooms::class)->checkRoomClosure();

        (new HandleExchangeRates())->updateExchangeRates($creatorUser);

        $rooms = BingoRoom::with([
            'Creator' => function ($query) use ($creatorUser) {
                $query->select('room', 'creator_user', 'status')
                    ->where('creator_user', $creatorUser)
                    ->where('status', 1);
            },
            'Config' => function ($query) {
                $query->select('room', 'game_mode', 'unix_date');
            }
        ])->select('id', 'encrypted', 'room_type', 'prize_pool')->where('status', 2)
            ->where(function ($query) use ($creatorUser) {
                $query->where('creator_user', $creatorUser)
                    ->orWhere('room_type', 'PB');
            })
            ->whereHas('Creator', function ($query) use ($creatorUser) {
                $query->where('creator_user', $creatorUser);
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

    public function getAvailableCountriesBySeller($roomID)
    {
        $seller = $this->seller(['id', 'commerce']);
        $creatorUser = Commerce::getValueById($seller->commerce, 'user');

        // Obtenemos las monedas disponibles para el vendedor y el comercio
        $sellerCoins = SellerCountriesCoins::getAvailableCoins($seller->id);
        $commerceCoins = CommerceExchangeRate::getCoinsByCommerce($seller->commerce, 'ACTIVE');

        // Obtenemos el campo allowed_coins (json o null) y lo forzamos a array
        $allowedCoinsRoom = BingoCreator::where('creator_user', $creatorUser)
            ->where('room', $roomID)->value('allowed_coins');

        $allowedCoinsRoom = is_array($allowedCoinsRoom) ? $allowedCoinsRoom : [];

        $coinsAvailable = array_values(array_intersect(
            array_map('strval', $sellerCoins),
            array_map('strval', $commerceCoins),
            array_map('strval', $allowedCoinsRoom)
        ));

        // Eliminamos '1' si existe
        $USDT = ($key = array_search('1', $coinsAvailable, true)) !== false;
        if ($USDT) {
            unset($coinsAvailable[$key]);
            $coinsAvailable = array_values($coinsAvailable);
        }

        $countriesAvailable = Coin::getCountriesByCoinInArray($coinsAvailable);

        $countries = Country::getCountries($countriesAvailable, ['id', 'name']);

        return ['countries' => $countries, 'USDT' => $USDT];
    }

    public function getCtnPrice($roomID)
    {
        $room = BingoRoom::with('Config:room,prize_pool_type,ctn_price')
            ->select('id', 'encrypted', 'prize_pool')
            ->where('encrypted', $roomID)->first();

        if (!$room) {
            return response()->json(['type' => 'info', 'message' => 'Sala no encontrada']);
        }

        $countriesCoins = $this->getAvailableCountriesBySeller($room->id);

        return response()->json([
            'prizePoolType' => $room->Config->prize_pool_type,
            'prizePool' => $room->prize_pool,
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

                $player = PlayerLink::select('room_user', 'name', 'lastname', 'country', 'phone', 'link')
                    ->where('phone', $request->player_info)->where('game', 1)->where('room', $room->id)->first();

                if (!$player)  return response()->json(['response' => false]);

                $route = $this->getGameLinkByPlayer($player->link);
                $ctnsPlayer = BingoCtns::where(['room_user' => $player->room_user, 'room' => $room->id])->count();

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
            if (!isset($data['error'])) {
                return response()->json($data);
            } else {
                return response()->json(['error' => 'El comercio no ha cargado los datos bancarios']);
            }
        } catch (\Exception $e) {
            // Manejar cualquier excepción que ocurra y retornar un error del servidor
            return response()->json(['error' => 'Ha ocurrido un error al procesar la solicitud.'], 500);
        }
    }

    public function getWinnerInfoForCtn($ctnId)
    {
        $ctnId = decrypt($ctnId);
        $roomUser = BingoPrizeCtns::getValueById($ctnId, 'room_user');
        $player = PlayerLink::getByRoomUser('BINGO', $roomUser, ['name', 'lastname', 'phone']);

        return json_encode(array(
            'name' => $player->name,
            'lastname' => $player->lastname,
            'phone' => $player->phone
        ));
    }

    public function getPlayerInfo($playerLink) // Obtención de la información jugador
    {
        $player = PlayerLink::where('link', $playerLink)
            ->select(['type_id', 'identification', 'phone'])->first();
        if ($player) return json_encode($player);
    }

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
            $newFileName = FileController::saveImage($filePayment, 'game/bingo/payments/all-ctns', 'PV');

            if (!$newFileName) {
                DB::rollBack();
                return redirect()->back()->with(['type' => 'warning', 'message' => 'Error al guardar el archivo del pago']);
            }

            if ($notifyAllCtns) {
                $notifyCtns = $this->notifyAllPendingLotCtns($seller, $newFileName);
            } else {
                $notifyCtns = $this->notifySpecificLotCtn($lotCtn, $newFileName);
            }

            if (!$notifyCtns) {
                Storage::delete('private/game/bingo/payments/all-ctns/' . $newFileName);
                DB::rollBack();

                Session::flash('type', 'warning');
                Session::flash('message', 'Error al notificar el pago');
            } else {
                DB::commit();
                Session::flash('message', 'Pago notificado con éxito');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al notificar el pago de la venta de cartones ctnsLotPayment(): ' . $e->getMessage());
        }

        return redirect()->back();
    }

    private function notifyAllPendingLotCtns($seller, $paymentProof)
    {
        $allPendingCtns = BingoCtns::with('Lot:id,ctns,sale_amount')
            ->whereHas('Lot', function ($query) use ($seller) {
                $query->where(['seller' => $seller, 'status' => 0]);
            })->where('seller', $seller)->get(['room', 'creator_user', 'room_user', 'coin', 'lot']);

        try {

            $paymentToInsert = [];
            $timestamp = now();

            foreach ($allPendingCtns as $ctn) {

                $ctnPrice = $ctn->Lot->sale_amount / $ctn->Lot->ctns;
                $playerLink = PlayerLink::getValueByRoomUser('BINGO', $ctn->room_user, 'id');

                $paymentToInsert[] = [
                    'payment' => 'SC',
                    'room' => $ctn->room,
                    'creator_user' => $ctn->creator_user,
                    'seller' => $seller,
                    'player_link' => $playerLink,
                    'lot' => $ctn->lot,
                    'coin' => $ctn->coin,
                    'amount' => $ctnPrice,
                    'payment_proof' => $paymentProof,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];
            }

            BingoCtnsPayment::insert($paymentToInsert);

            BingoCtnsLot::where('seller', $seller)->where('status', 0)->update(['status' => 1]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error al registrar y/o notificar la creacion y venta de todos los lotes de cartones: ' . $e->getMessage());
            return false;
        }
    }

    private function notifySpecificLotCtn($lotCtn, $paymentProof)
    {
        try {
            $this->registerCartonPayment('SC', $lotCtn, $paymentProof);

            BingoCtnsLot::where('id', $lotCtn->id)->where('status', 0)->update(['status' => 1]);
            return true;
        } catch (\Exception $e) {
            Log::error("Error al registrar y/o notificar la creacion venta del lote de cartones LOTE [$lotCtn->id] ::: " . $e->getMessage());
            return false;
        }
    }
}
