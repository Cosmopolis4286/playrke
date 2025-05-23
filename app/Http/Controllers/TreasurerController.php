<?php

namespace App\Http\Controllers;

use App\Traits\EncryptRKE;
use App\Models\User;
use App\Models\Coin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Models\OtherTreasurers;
use App\Models\Treasurer;
use App\Models\TreasurerCountriesCoins;
use App\Models\TreasurerExchangeRate;
use App\Models\PhoneNumbers;
use App\Models\UserType;
use App\Models\UUIDCodes;
use App\Models\Wallet;
use App\Traits\Filters;
use App\Traits\RouteRKE;
use App\Traits\UserVal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TreasurerController extends Controller
{
    use EncryptRKE, UserVal, RouteRKE, Filters;

    private function validateRequest($request, $type = 1, $id = '')
    {
        // Si es tipo 2, encuentra al usuario usando el ID descifrado.
        if ($type == 2) {
            $treasurer = Treasurer::findOrFail(decrypt($id));
        }

        // Definir reglas de validación.
        $rules = [
            'username' => ['required', 'string', 'max:20', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:40', 'unique:users,email'],
            'country' => ['required', 'numeric', 'exists:countries,id'],
            'time_zone' => ['required', 'numeric', 'exists:time_zones,id'],
            'coin' => ['required', 'numeric', 'exists:coins,id'],
            'name' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:20', 'unique:treasurers,phone'],
            'other_coins.*' => ['exists:coins,id'],
        ];

        // Ajustar reglas de validación si es una actualización.
        if ($type == 2) {
            $rules['username'] = ['required', 'string', 'max:20', 'unique:users,username,' . $treasurer->user];
            $rules['email'] = ['required', 'string', 'email', 'max:40', 'unique:users,email,' . $treasurer->user];
            $rules['phone'] = ['required', 'string', 'max:20', 'unique:treasurers,phone,' . $treasurer->id];
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

    public function create(Request $request) /// Registar Comercializadoras
    {
        if ($this->user(['user_type'])->user_type != 2) {
            return redirect()->back()->withErrors(['error' => 'No tienes permiso para realizar esta acción.']);
        }

        DB::beginTransaction();

        try {

            $userToPromote = $request->user_to_promote;
            $otherCoins = $request->other_coins;
            $allowedGames = $request->allowed_games;

            if ($userToPromote) {

                $existsTreasurer = OtherTreasurers::existsByCreatorUser($userToPromote);

                if ($existsTreasurer) {
                    return redirect()->back()->with('message', 'Este usuario ya es un Tesorero')
                        ->with('type', 'warning');
                }

                $allowedGames = $allowedGames ? implode(';', $allowedGames) : 'ALL';

                OtherTreasurers::create([
                    'creator_user' => $userToPromote,
                    'allowed_games' =>  $allowedGames,
                    'allowed_coins' => implode(';', $otherCoins),
                ]);

                DB::commit();
                Session::flash('message', 'Registro exitoso');
                return redirect()->back();
            }

            // Validar la solicitud usando la función validateRequest
            $validator = $this->validateRequest($request);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $newPassw = $this->valEncrypt();
            $phoneNumber = $request->phone;

            $newUser = User::create([
                'username' => $request->username,
                'user_type' => UserType::getValueByPrefix('treasurer', 'id'),
                'email' => $request->email,
                'password' => Hash::make($newPassw),
                'country' => $request->country,
                'time_zone' => $request->time_zone,
                'email_verified_at' => Carbon::now(),
                'referral_code' => User::getUniqueReferralCode(),
            ])->id;

            // Crear un nuevo Wallet para el usuario
            Wallet::create(['coin' => $request->coin, 'user' => $newUser]);

            PhoneNumbers::create([
                'user' => $newUser,
                'country' => $request->country,
                'phone_number' => $phoneNumber
            ]);

            $treasurer = Treasurer::create([
                'name' => $request->name,
                'phone' => $phoneNumber,
                'coin' => $request->coin,
                'country' => $request->country,
                'status' => 1,
                'user' => $newUser
            ]);

            // Registrar Code UUID
            UUIDCodes::create([
                'code_uuid' => $treasurer->code_uuid,
                'user' => $newUser,
                'user_type' => UserType::getValueByPrefix('treasurer', 'id'),
            ]);

            // Registar moneda Principal
            TreasurerCountriesCoins::create([
                'treasurer' => $treasurer->id,
                'country' => $treasurer->country,
                'coin' => $treasurer->coin,
                'level' => '1',
                'creator_user' => $newUser,
                'status' => 1
            ]);

            $creatorUser = [
                'model' => 'Treasurer',
                'id' => $treasurer->id,
                'userId' => $newUser
            ];

            if ($otherCoins) {
                // Insertar otras monedas
                $this->insertOtherCoins($creatorUser, $otherCoins);
            }

            DB::commit();

            // Mensajes flash con tiempo de expiración personalizado
            Session::flash('message', 'Registro exitoso');
            Session::flash('message-passw', $newPassw);
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear Treasurer: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Hubo un error al procesar la solicitud.']);
        }
    }

    public function getInfo($id) // Obtener Info Comercializadora
    {
        $id = decrypt($id);
        $treasurer = Treasurer::getById($id, ['name', 'country', 'coin', 'phone']);
        $otherCoins = TreasurerCountriesCoins::getAvailableCoins($id);
        $user = User::getById(Treasurer::getValueById($id, 'user'), ['username', 'email', 'time_zone']);

        $data = [
            'treasurer' => $treasurer,
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

        DB::beginTransaction();

        try {

            // Decryptar el ID y encontrar el vendedor
            $treasurer = Treasurer::findOrFail(decrypt($id));

            if ($treasurer) {

                $validator = $this->validateRequest($request, 2, $id);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator)->withInput();
                }

                $userID = $treasurer->user;
                $userTimeZone = User::getValueById($userID, 'time_zone');

                if ($userTimeZone <> $request->time_zone) {
                    User::where('id', $userID)->update(['time_zone' => $request->time_zone]);
                }

                // Verificar si la moneda principal ha cambiado
                $coinChange = $treasurer->coin !== $request->coin;

                // Actualizar los campos del vendedor
                $treasurer->update([
                    'name' => $request->name,
                    'country' => $request->country,
                    'coin' => $request->coin,
                    'phone' => $request->phone
                ]);

                $phoneNumber = $request->phone;

                if ($phoneNumber <> $treasurer->phone) {
                    PhoneNumbers::where('user', $treasurer->user)->update([
                        'country' => $request->country,
                        'phone_number' => $phoneNumber
                    ]);
                }

                if ($coinChange) {
                    // Actualizar moneda Principal
                    TreasurerCountriesCoins::where('creator_user', $treasurer->user)
                        ->where('coin', $treasurer->coin)
                        ->update([
                            'country' => $treasurer->country,
                            'coin' => $treasurer->coin
                        ]);
                }

                $creatorUser = [
                    'model' => 'Treasurer',
                    'id' => $treasurer->id,
                    'userId' => $treasurer->user
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
        //Treasurer::destroy(decrypt($request->id));
        Treasurer::where('id', decrypt($id))->update(['status' => 3]);
        Session::flash('message', 'El Comercio fue eliminado');
        return redirect()->back();
    }

    public function changeStatus($id)
    {
        $id = decrypt($id);
        $treasurerStatus = (int)!Treasurer::getValueById($id, 'status');
        Treasurer::where('id', $id)->update(['status' => $treasurerStatus]);

        Session::flash('message', 'Status actualizado');
        return redirect()->back()->with('type', 'success');
    }

    public function changePassword($id)
    {
        $res = $this->handleChangePassword('Treasurer', $id);

        if (isset($res['PWD'])) {
            $password = $res['PWD'];
            Session::flash('message', "La nueva contraseña del Comercio es: <b>$password</b>");
            Session::flash('message-passw', $password);
        } else {
            Session::flash('msg-error', 'Ha ocurrido un error');
        }
        return redirect()->back();
    }

    public function getPaymentTreasurerDetails($treasurer, $coin)
    {
        $treasurerID = decrypt($treasurer);
        $coin = decrypt($coin);

        $treasurer = Treasurer::getById($treasurerID, ['name', 'user']);
        // Obtener detalles de pago por tesorero usando el ID del usuario autenticado y la moneda desencriptada
        $paymentDetails = $this->getPaymentDetailsByUserId($treasurer->user, $coin);

        if (!$paymentDetails) {
            return response()->json(['Error' => 'El tesorero no ha cargado los datos bancarios']);
        }

        $treasurer = $treasurer->name;

        return response()->json(compact('treasurer', 'paymentDetails', 'exchangeRate'));
    }
}
