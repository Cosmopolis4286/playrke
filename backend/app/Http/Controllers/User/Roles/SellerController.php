<?php

namespace App\Http\Controllers\User\Roles;

use App\Models\BingoRoom;
use App\Models\BingoCtns;
use App\Models\BingoCtnsLot;
use App\Models\ExternalPlayers;
use App\Models\Seller;
use App\Models\DocumentType;
use App\Traits\Helpers\EncryptTrait;
use App\Traits\Games\BingoTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BingoCreator;
use App\Models\Games\Bingo\Cards\CardPrice\BingoCardPrice;
use App\Models\BingoCtnsPayment;
use App\Models\BingoPrizeCtns;
use App\Models\Currencies\Currency;
use App\Models\Commerce;
use App\Models\Payments\ExchangeRate\Users\Roles\CommerceExchangeRate;
use App\Models\Country;
use App\Models\PhoneNumbers;
use App\Models\SellerCountryCurrency;
use App\Models\Users;
use App\Models\Role;
use App\Models\Wallet;
use App\Services\Games\Bingo\HandleBingoRooms;
use App\Services\Helpers\HandleExchangeRates;
use App\Traits\Helpers\FiltersTrait;
use App\Traits\Users\ExternalPlayerTrait;
use App\Traits\Users\UserTrait;
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
    use EncryptTrait, BingoTrait, ExternalPlayerTrait,  UserTrait, FiltersTrait;

    /**
     * Valida la solicitud para crear o actualizar un vendedor (Seller).
     *
     * @param \Illuminate\Http\Request $request Objeto request con los datos a validar.
     * @param bool $isUpdate Indica si la validación es para actualización (true) o creación (false).
     * @param string|null $encryptedId ID cifrado del vendedor para validaciones únicas en actualización.
     * @return \Illuminate\Contracts\Validation\Validator Validador con el resultado.
     */
    private function validateRequest($request, bool $isUpdate = false, ?string $encryptedId = null)
    {
        $rules = [
            'username' => ['required', 'string', 'max:20', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:40', 'unique:users,email'],
            'country' => ['required', 'numeric', 'exists:countries,id'],
            'time_zone' => ['required', 'numeric', 'exists:time_zones,id'],
            'currency' => ['required', 'numeric', 'exists:currencies,id'],
            'name' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:20', 'unique:sellers,phone'],
            'commission' => ['required', 'numeric', 'between:0,25'],
            'other_currencies.*' => ['exists:currencies,id'],
        ];

        if ($isUpdate && $encryptedId) {
            $seller = Seller::findOrFail(decrypt($encryptedId));
            $rules['username'] = ['required', 'string', 'max:20', 'unique:users,username,' . $seller->user];
            $rules['email'] = ['required', 'string', 'email', 'max:40', 'unique:users,email,' . $seller->user];
            $rules['phone'] = ['required', 'string', 'max:20', 'unique:sellers,phone,' . $seller->id];
        }

        $messages = [
            'username.unique' => 'Nombre de usuario no disponible.',
            'email.unique' => 'Este correo ya está registrado.',
            'phone.unique' => 'Este celular (' . $request->phone . ') ya está registrado.',
            'other_currencies.*.exists' => 'Uno o más de los otros currencies seleccionados no existen en la base de datos.',
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Crea un nuevo registro de Seller junto con su usuario, wallet, teléfono y monedas asociadas.
     *
     * Flujo:
     *  - Verifica permisos del usuario.
     *  - Valida la solicitud.
     *  - Genera y encripta la nueva contraseña.
     *  - Crea el usuario, wallet, teléfono y seller.
     *  - Registra moneda principal y otras monedas si existen.
     *  - Maneja transacciones para asegurar consistencia.
     *  - Loguea éxito o error y redirige con mensajes flash.
     *
     * @param Request $request Datos enviados desde el formulario.
     * @return RedirectResponse Redirige con mensajes de éxito o error.
     */
    public function create(Request $request)
    {
        // Verificar permiso basado en tipo de usuario
        if (!in_array($this->user(['user_type'])->user_type, [2, 3])) {
            return redirect()->back()->withErrors(['error' => 'No tienes permiso para realizar esta acción.']);
        }

        DB::beginTransaction();

        try {
            // Validar la solicitud y retornar errores si falla
            $validator = $this->validateRequest($request);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Generar y encriptar la nueva contraseña
            $newPassw = $this->valEncrypt();

            // Crear el usuario nuevo
            $newUser = User::create([
                'username' => $request->username,
                'user_type' => Role::getValueByPrefix('seller', 'id'),
                'email' => $request->email,
                'password' => Hash::make($newPassw),
                'country' => $request->country,
                'time_zone' => $request->time_zone,
                'email_verified_at' => Carbon::now(),
                'referral_code' => User::getUniqueReferralCode(),
            ])->id;

            // Crear Wallet asociado al usuario
            Wallet::create([
                'currency' => $request->currency,
                'user' => $newUser,
            ]);

            // Registrar número telefónico del usuario
            PhoneNumbers::create([
                'user' => $newUser,
                'country' => $request->country,
                'phone_number' => $request->phone,
            ]);

            // Crear el registro Seller asociado
            $seller = Seller::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'currency' => $request->currency,
                'country' => $request->country,
                'commerce_id' => Commerce::getValueByUserId(Auth::id(), 'id'),
                'commission' => $request->commission,
                'status' => 1,
                'user' => $newUser,
            ]);

            // Registrar moneda principal para Seller
            SellerCountryCurrency::create([
                'seller' => $seller->id,
                'country' => $seller->country,
                'currency' => $seller->currency,
                'level' => '1',
                'creator_user_id' => $newUser,
                'status' => 1,
            ]);

            // Insertar monedas adicionales si hay
            if ($request->other_currencies) {
                $this->insertOtherCurrencies(
                    [
                        'model' => 'Seller',
                        'id' => $seller->id,
                        'userId' => $newUser,
                    ],
                    $request->other_currencies
                );
            }

            DB::commit();

            // Log de éxito con info relevante
            Log::info("Seller creado exitosamente", [
                'seller_id' => $seller->id,
                'user_id' => $newUser,
                'username' => $request->username,
                'email' => $request->email,
            ]);

            // Mensajes flash para feedback en UI
            Session::flash('message', 'Registro exitoso');
            Session::flash('pawss', $newPassw);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log detallado del error
            Log::error('Error al crear Seller: ' . $e->getMessage(), [
                'request_data' => $request->all(),
            ]);

            return redirect()->back()->withErrors(['error' => 'Error al procesar la solicitud']);
        }

        return redirect()->back();
    }

    /**
     * Obtiene la información detallada de un vendedor a partir de un ID encriptado.
     *
     * Este método:
     *  - Desencripta el ID recibido.
     *  - Recupera datos del vendedor, usuario asociado y monedas disponibles.
     *  - Retorna la información en formato JSON.
     *
     * @param string $encryptedId ID encriptado del vendedor.
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con la información del vendedor.
     */
    public function getInfo(string $encryptedId): \Illuminate\Http\JsonResponse
    {
        try {
            // Desencriptar el ID recibido
            $id = decrypt($encryptedId);
        } catch (\Throwable $e) {
            // Si el ID es inválido o no se puede desencriptar, retornar error controlado
            return response()->json([
                'error' => 'ID inválido o corrupto',
                'message' => $e->getMessage()
            ], 400);
        }

        // Obtener datos del vendedor
        $seller = Seller::getById($id, ['name', 'commerce_id', 'commission', 'country', 'phone']);

        // Obtener monedas adicionales disponibles para el vendedor
        $otherCurrencies = SellerCountryCurrency::getAvailableCurrencies($id);

        // Obtener datos del usuario asociado al vendedor
        $userId = Seller::getValueById($id, 'user');
        $user = User::getById($userId, ['username', 'email']);

        // Preparar respuesta estructurada
        $data = ['seller' => $seller, 'user' => $user, 'otherCurrencies' => $otherCurrencies];

        // Retornar datos en formato JSON
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

                $userId = $seller->user;
                $userTimeZone = User::getValueById($userId, 'time_zone');

                if ($userTimeZone <> $request->time_zone) {
                    User::where('id', $userId)->update(['time_zone' => $request->time_zone]);
                }

                // Verificar si la moneda principal ha cambiado
                $currencyChange = $seller->currency !== $request->currency;

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
                    'currency' => $request->currency,
                    'phone' => $phoneNumber,
                    'commission' => $request->commission
                ]);

                if ($currencyChange) {
                    // Actualizar moneda Principal
                    SellerCountryCurrency::where('seller', $seller->id)
                        ->where('currency', $seller->currency)->update([
                            'country' => $seller->country,
                            'currency' => $seller->currency
                        ]);
                }

                $creatorUser = [
                    'model' => 'Seller',
                    'id' => $seller->id,
                    'userId' => $seller->user
                ];

                $otherCurrencies = $request->other_currencies;

                if ($otherCurrencies) {
                    // Insertar otras monedas
                    $this->insertOtherCurrencies($creatorUser, $otherCurrencies);
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
        Session::flash('pawss', $newPassw);
        return redirect()->back();
    }

    public function cartonGenerate()
    {
        $documentType = DocumentType::where('country', 234)->get();

        $seller = $this->seller(['commerce_id', 'country', 'commission', 'status']);

        $creatorUser = Commerce::getValueById($seller->commerce, 'user');

        app(HandleBingoRooms::class)->checkRoomClosure();

        (new HandleExchangeRates())->updateExchangeRates($creatorUser);

        $rooms = BingoRoom::with([
            'Creator' => function ($query) use ($creatorUser) {
                $query->select('room', 'creator_user_id', 'status')
                    ->where('creator_user_id', $creatorUser)
                    ->where('status', 1);
            },
            'Config' => function ($query) {
                $query->select('room', 'game_mode', 'unix_date');
            }
        ])->select('id', 'encrypted', 'room_type', 'prize_pool')->where('status', 2)
            ->where(function ($query) use ($creatorUser) {
                $query->where('creator_user_id', $creatorUser)
                    ->orWhere('room_type', 'PB');
            })
            ->whereHas('Creator', function ($query) use ($creatorUser) {
                $query->where('creator_user_id', $creatorUser);
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
            'commission' => $seller->commn,
            'country' => $seller->country,
            'rooms' => $rooms
        ]);
    }

    public function getAvailableCountriesBySeller($roomId)
    {
        $seller = $this->seller(['id', 'commerce_id']);
        $creatorUser = Commerce::getValueById($seller->commerce, 'user');

        // Obtenemos las monedas disponibles para el vendedor y el comercio
        $sellerCurrencies = SellerCountryCurrency::getAvailableCurrencies($seller->id);
        $commerceCurrencies = CommerceExchangeRate::getCurrenciesByCommerce($seller->commerce, 'ACTIVE');

        // Obtenemos el campo allowed_currencies (json o null) y lo forzamos a array
        $allowedCurrenciesRoom = BingoCreator::where('creator_user_id', $creatorUser)
            ->where('room', $roomId)->value('allowed_currencies');

        $allowedCurrenciesRoom = is_array($allowedCurrenciesRoom) ? $allowedCurrenciesRoom : [];

        $currenciesAvailable = array_values(array_intersect(
            array_map('strval', $sellerCurrencies),
            array_map('strval', $commerceCurrencies),
            array_map('strval', $allowedCurrenciesRoom)
        ));

        // Eliminamos '1' si existe
        $USDT = ($key = array_search('1', $currenciesAvailable, true)) !== false;
        if ($USDT) {
            unset($currenciesAvailable[$key]);
            $currenciesAvailable = array_values($currenciesAvailable);
        }

        $countriesAvailable = Currency::getCountriesByCurrencyInArray($currenciesAvailable);

        $countries = Country::getCountries($countriesAvailable, ['id', 'name']);

        return ['countries' => $countries, 'USDT' => $USDT];
    }

    public function getCtnPrice($roomId)
    {
        $room = BingoRoom::with('Config:room,prize_pool_type,card_price')
            ->select('id', 'encrypted', 'prize_pool')
            ->where('encrypted', $roomId)->first();

        if (!$room) {
            return response()->json(['type' => 'info', 'message' => 'Sala no encontrada']);
        }

        $countriesCurrencies = $this->getAvailableCountriesBySeller($room->id);

        return response()->json([
            'prizePoolType' => $room->Config->prize_pool_type,
            'prizePool' => $room->prize_pool,
            'currency' => 'USDT',
            'price' => $room->Config->card_price,
            'countries' => $countriesCurrencies['countries'],
            'USDT' => $countriesCurrencies['USDT']
        ]);
    }

    public function getCtnPriceCurrency($room, $country)
    {
        // Obtener moneda según el país
        $currency = Currency::getCurrencyByCountry($country, ['id', 'symbol']);
        if (!$currency) {
            return response()->json(['error' => 'Moneda no encontrada'], 404);
        }

        // Obtener comercio asociado al usuario autenticado
        $commerce = Seller::getValueByUserId(Auth::id(), 'commerce_id');
        if (!$commerce) {
            return response()->json(['error' => 'Comercio no encontrado'], 404);
        }

        // Obtener tasa de cambio
        $exchangeRate = CommerceExchangeRate::getCommerceCurrencyValue($commerce, $currency->id);

        $roomId = BingoRoom::getValueByEncrypted($room, 'id');
        $creator = ['type' => 'commerce_id', 'id' => $commerce];

        $setPrice = false;
        $value = $exchangeRate;

        //Obtener $bingoCreatorID
        $bingoCreatorID = BingoCreator::getValueByCreatorRoom($creator, $roomId, 'id');

        // Intentar obtener el precio configurado
        $ctnPrice = BingoCardPrice::getValueCtnPrice($roomId, $currency->id, $bingoCreatorID);

        if ($ctnPrice) {
            $setPrice = true;
            $value = $ctnPrice;
        }

        // Construir y devolver la respuesta
        return response()->json([
            'sym' => $currency->symbol,
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

                $player = ExternalPlayers::select('room_user', 'name', 'lastname', 'country', 'phone', 'link')
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

    public function getPaymentCommerceDetails($currency)
    {
        try {
            $user = Commerce::getValueById($this->seller('commerce_id')->commerce, 'user');

            // Obtener detalles de pago por comercio usando el ID del usuario autenticado y la moneda decriptada
            $data = $this->getPaymentDetailsByUserId($user, decrypt($currency));

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
        $player = ExternalPlayers::getByRoomUser('BINGO', $roomUser, ['name', 'lastname', 'phone']);

        return json_encode(array(
            'name' => $player->name,
            'lastname' => $player->lastname,
            'phone' => $player->phone
        ));
    }

    public function getPlayerInfo($playerLink) // Obtención de la información jugador
    {
        $player = ExternalPlayers::where('link', $playerLink)
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
            })->where('seller', $seller)->get(['room', 'creator_user_id', 'room_user', 'currency', 'lot']);

        try {

            $paymentToInsert = [];
            $timestamp = now();

            foreach ($allPendingCtns as $ctn) {

                $ctnPrice = $ctn->Lot->sale_amount / $ctn->Lot->ctns;
                $playerLink = ExternalPlayers::getValueByRoomUser('BINGO', $ctn->room_user, 'id');

                $paymentToInsert[] = [
                    'payment' => 'SC',
                    'room' => $ctn->room,
                    'creator_user_id' => $ctn->creator_user,
                    'seller' => $seller,
                    'external_player_id' => $playerLink,
                    'lot' => $ctn->lot,
                    'currency' => $ctn->currency,
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
