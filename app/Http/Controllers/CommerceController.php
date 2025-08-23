<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

// Models
use App\Models\User;
use App\Models\Coin;
use App\Models\Seller;
use App\Models\BingoCtns;
use App\Models\BingoCtnsLot;
use App\Models\Commerce;
use App\Models\CommerceCountriesCoins;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\Games;
use App\Models\PhoneNumbers;
use App\Models\SellerCountriesCoins;
use App\Models\TimeZone;
use App\Models\UserType;
use App\Models\UUIDCodes;
use App\Models\Wallet;

// Traits
use App\Traits\Games\BingoTrait;
use App\Traits\Helpers\EncryptTrait;
use App\Traits\Helpers\FiltersTrait;
use App\Traits\Users\UserValTrait;

// Otros
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CommerceController extends Controller
{
    use BingoTrait, FiltersTrait, EncryptTrait, UserValTrait;

    private function validateRequest($request, $type = 1, $id = '')
    {
        // Si es tipo 2, encuentra al usuario usando el ID descifrado.
        if ($type == 2) {
            $commerce = Commerce::findOrFail(decrypt($id));
        }

        // Definir reglas de validación.
        $rules = [
            'username' => ['required', 'string', 'max:20', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:40', 'unique:users,email'],
            'country' => ['required', 'numeric', 'exists:countries,id'],
            'time_zone' => ['required', 'numeric', 'exists:time_zones,id'],
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

    /**
     * Crea una nueva Comercializadora (Commerce).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request)
    {
        // 🔒 Verificación de permisos
        if ($this->user(['user_type'])->user_type !== 2) {
            return redirect()->back()->withErrors([
                'error' => 'No tienes permiso para realizar esta acción.'
            ]);
        }

        $codeRKE = $request->code_rke;

        // 🔎 Validación de código duplicado
        if (Commerce::existsValue('code_rke', $codeRKE)) {
            return redirect()->back()->with([
                'message' => 'El código RKE ya existe',
                'type' => 'warning'
            ]);
        }

        // 🔍 Validación inicial antes de iniciar transacción
        $validator = $this->validateRequest($request);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $newPassw = $this->valEncrypt();
            $userTypeId = UserType::getValueByPrefix('commerce', 'id');

            // 👤 Crear usuario
            $newUser = User::create([
                'username'          => $request->username,
                'user_type'         => $userTypeId,
                'email'             => $request->email,
                'password'          => Hash::make($newPassw),
                'country'           => $request->country,
                'time_zone'         => $request->time_zone,
                'email_verified_at' => Carbon::now(),
                'referral_code'     => User::getUniqueReferralCode(),
            ])->id;

            // 💰 Wallet
            Wallet::create([
                'coin' => $request->coin,
                'user' => $newUser
            ]);

            // 📞 Teléfono
            $phoneNumber = $request->phone;
            PhoneNumbers::create([
                'user'         => $newUser,
                'country'      => $request->country,
                'phone_number' => $phoneNumber
            ]);

            // 🏢 Commerce principal
            $commerce = Commerce::create([
                'name'      => $request->name,
                'phone'     => $phoneNumber,
                'coin'      => $request->coin,
                'country'   => $request->country,
                'status'    => 1,
                'user'      => $newUser,
                'code_rke'  => $this->replaceLettersWithNumbers($codeRKE),
                'code_uuid' => $this->getCodeUUID('Commerce')
            ]);

            // Moneda principal
            CommerceCountriesCoins::create([
                'commerce'     => $commerce->id,
                'country'      => $commerce->country,
                'coin'         => $commerce->coin,
                'level'        => '1',
                'creator_user' => $newUser,
                'status'       => 1
            ]);

            // Configuración de usuario creador
            $creatorUser = [
                'model'  => 'Commerce',
                'id'     => $commerce->id,
                'userId' => $newUser
            ];

            // Juegos permitidos
            if ($request->allowed_games) {
                $this->handleUserGamePermission($creatorUser, $request->allowed_games);
            }

            // Otras monedas
            if ($request->other_coins) {
                $this->insertOtherCoins($creatorUser, $request->other_coins);
            }

            // Registrar Code UUID
            UUIDCodes::create([
                'code_uuid'  => $commerce->code_uuid,
                'user'       => $newUser,
                'user_type'  => $userTypeId,
            ]);

            DB::commit();

            // ✅ Mensaje de éxito (y envío de contraseña generada)
            return redirect()->back()->with([
                'message' => 'Registro exitoso',
                'type'    => 'success',
                'passw'   => $newPassw
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error al crear Commerce: ' . $e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Hubo un error al procesar la solicitud.'
            ]);
        }
    }

    public function getInfo($id) // Obtener Info Comercializadora
    {
        $id = decrypt($id);
        $commerce = Commerce::getById($id, ['name', 'country', 'coin', 'phone', 'code_rke']);
        $otherCoins = CommerceCountriesCoins::getAvailableCoins($id);
        $user = User::getById(Commerce::getValueById($id, 'user'), ['username', 'email', 'time_zone']);

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

                $userID = $commerce->user;
                $userTimeZone = User::getValueById($userID, 'time_zone');

                if ($userTimeZone <> $request->time_zone) {
                    User::where('id', $userID)->update(['time_zone' => $request->time_zone]);
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

                $phoneNumber = $request->phone;

                if ($phoneNumber <> $commerce->phone) {
                    PhoneNumbers::where('user', $commerce->user)->update([
                        'country' => $request->country,
                        'phone_number' => $phoneNumber
                    ]);
                }

                if ($coinChange) {
                    // Actualizar moneda Principal
                    CommerceCountriesCoins::where('creator_user', $commerce->user)
                        ->where('coin', $commerce->coin)
                        ->update([
                            'country' => $commerce->country,
                            'coin' => $commerce->coin
                        ]);
                }

                $creatorUser = [
                    'model' => 'Commerce',
                    'id' => $commerce->id,
                    'userId' => $commerce->user
                ];

                $otherCoins = $request->other_coins;
                $allowedGames = $request->allowed_games;

                if ($allowedGames) {
                    // Insertar juegos permitidos
                    $this->handleUserGamePermission($creatorUser, $allowedGames);
                }

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
            Session::flash('pawss', $password);
        } else {
            Session::flash('msg-error', 'Ha ocurrido un error');
        }
        return redirect()->back();
    }

    /**
     * Muestra el listado y formulario de vendedores asociados al comercio del usuario autenticado.
     *
     * @return \Illuminate\View\View
     */
    public function sellers()
    {
        // Datos básicos para la vista
        $title = 'Mis Vendedores';
        $moduleId = 'seller';

        // Obtener el ID del comercio del usuario autenticado
        $commerceId = $this->commerce()->id;

        // Cargar vendedores con relaciones necesarias (evitando consultas N+1)
        $sellers = Seller::with(['Country', 'CountriesCoins.Coin'])
            ->where('commerce', $commerceId)->get();

        // Obtener países disponibles para el usuario
        $avaliableCountries = $this->getAvailableCountries(Auth::id());
        $countries = Country::getCountries($avaliableCountries, ['id', 'name']);

        // Obtener monedas disponibles para el usuario
        $avaliableCoins = $this->getAvailableCoins(Auth::id());
        $coins = Coin::getCoinsById($avaliableCoins, ['id', 'symbol', 'short_name', 'long_name', 'country']);

        // Zonas horarias activas
        $timeZones = TimeZone::select('id', 'name', 'country')->where('status', 1)->get();

        // Juegos disponibles
        $games = Games::getAvailableGames();

        // Retornar vista con todos los datos
        return view(
            'modules.seller.form.index',
            compact('coins', 'countries', 'games', 'sellers', 'timeZones', 'title', 'moduleId')
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

    public function getPaymentCommerceDetails($commerce, $coin)
    {
        $commerceID = decrypt($commerce);
        $coin = decrypt($coin);

        $commerce = Commerce::getById($commerceID, ['name', 'user']);
        // Obtener detalles de pago por comercio usando el ID del usuario autenticado y la moneda desencriptada
        $paymentDetails = $this->getPaymentDetailsByUserId($commerce->user, $coin);

        if (!$paymentDetails) {
            return response()->json(['Error' => 'El comercio no ha cargado los datos bancarios']);
        }

        $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($commerceID, $coin);
        $commerce = $commerce->name;

        return response()->json(compact('commerce', 'paymentDetails', 'exchangeRate'));
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
}
