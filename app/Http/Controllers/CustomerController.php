<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Models\UserType;
use App\Models\UUIDCodes;
use App\Models\Wallet;
use App\Traits\EncryptRKE;
use App\Traits\UserVal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    use EncryptRKE, UserVal;

    private function validateRequest($request, $type = 1, $id = '')
    {
        // Si es tipo 2, encuentra al Cliente usando el ID descifrado.
        if ($type == 2) {
            $commerce = Customer::findOrFail(decrypt($id));
        }

        // Definir reglas de validación.
        $rules = [
            'username' => ['required', 'string', 'max:20', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:40', 'unique:users,email'],
            'country' => ['required', 'numeric', 'exists:countries,id'],
            'coin' => ['required', 'numeric', 'exists:coins,id'],
            'name' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:20', 'unique:sellers,phone'],
            'other_coins.*' => ['exists:coins,id'],
        ];

        // Ajustar reglas de validación si es una actualización.
        if ($type == 2) {
            $rules['username'] = ['required', 'string', 'max:20', 'unique:users,username,' . $commerce->user];
            $rules['email'] = ['required', 'string', 'email', 'max:40', 'unique:users,email,' . $commerce->user];
            $rules['phone'] = ['required', 'string', 'max:20', 'unique:sellers,phone,' . $commerce->id];
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
        if (!in_array($this->user()->user_type, [2])) {
            return redirect()->back()->withErrors(['error' => 'No tienes permiso para realizar esta acción.']);
        }

        $codeRKE = $request->code_rke;
        $existsCodeRKE = Customer::existsValue('code_rke', $codeRKE);

        if ($existsCodeRKE) {
            return redirect()->back()->with('message', 'El código RKE ya existe')->with('type', 'warning');
        }

        date_default_timezone_set('America/Caracas');

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
                'referral_code' => User::getUniqueReferralCode(),
            ]);

            // Crear un nuevo Wallet para el usuario
            Wallet::create(['coin' => $request->coin, 'user' => $newUser->id]);

            $commerce = Customer::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'coin' => $request->coin,
                'country' => $request->country,
                'status' => 1,
                'user' => $newUser->id,
                'code_rke' => $codeRKE,
                'code_uuid' => $this->getCodeUUID()
            ]);

            // // Registar moneda Principal
            // CustomerCountriesCoins::create([
            //     'commerce' => $commerce->id,
            //     'country' => $commerce->country,
            //     'coin' => $commerce->coin,
            //     'status' => 1
            // ]);

            // $otherCoins = $request->other_coins;

            // if (!empty($otherCoins) && is_array($otherCoins)) {
            //     $otherCoins = Coin::getCoinsById($otherCoins);
            //     $timestamp = now();

            //     $coinsToCreate = [];
            //     foreach ($otherCoins as $coin) {
            //         $coinsToCreate[] = [
            //             'commerce' => $commerce->id,
            //             'country' => $coin->country,
            //             'coin' => $coin->id,
            //             'status' => 1,
            //             'created_at' => $timestamp,
            //             'updated_at' => $timestamp
            //         ];
            //     }
            //     CustomerCountriesCoins::insert($coinsToCreate);
            // }

            // Registrar Code UUID
            UUIDCodes::create([
                'code' => $commerce->code_uuid,
                'user_type' => UserType::getValueByPrefix('customer', 'id')
            ]);

            DB::commit();

            // Mensajes flash con tiempo de expiración personalizado
            Session::flash('message', "Cliente $request->name registrado con éxito");
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

        $customer = Customer::getById($id, ['name', 'country', 'coin', 'phone']);
        $user = User::getById(Customer::getValueById($id, 'user'), ['username', 'email']);

        $data = [
            'customer' => $customer,
            'user' => $user
        ];

        return response()->json($data);
    }

    public function update(Request $request, $id) // Editar Comercializadora
    {
        if (!in_array($this->user()->user_type, [2])) {
            return redirect()->back()->withErrors(['error' => 'No tienes permiso para realizar esta acción.']);
        }

        $codeRKE = $request->code_rke;
        $existsCodeRKE = Customer::existsValue('code_rke', $codeRKE);

        if ($existsCodeRKE) {
            return redirect()->back()->with('message', 'El código RKE ya existe')->with('type', 'warning');
        }

        DB::beginTransaction();

        try {
            // Decryptar el ID y encontrar el vendedor
            $commerce = Customer::findOrFail(decrypt($id));

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
                    'code_rke' => $request->code_rke
                ]);

                // if ($coinChange) {
                //     // Actualizar moneda Principal
                //     CustomerCountriesCoins::where('commerce', $commerce->id)
                //         ->where('coin', $commerce->coin)->update([
                //             'country' => $commerce->country,
                //             'coin' => $commerce->coin
                //         ]);
                // }

                // $otherCoins = $request->other_coins;
                // $this->insertOtherCoins($commerce->id, $otherCoins);

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
        //Customer::destroy(decrypt($request->id));
        Customer::where('id', decrypt($id))->update(['status' => 3]);

        Session::flash('message', 'El Cliente fue eliminado');
        return redirect()->back();
    }

    public function changeStatus($id)
    {
        DB::beginTransaction();

        try {
            $id = decrypt($id);

            // Obtener el estado del cliente
            $customerStatus = Customer::getValueById($id, 'status');

            // Determinar si el cliente está eliminado
            $isDeleted = $customerStatus == 3 ? true : false;

            // Determinar el estado basado en si está eliminado o no
            $status = $isDeleted ? 1 : (int)!$customerStatus;

            Customer::where('id', $id)->update(['status' => $status]);

            DB::commit();
            Session::flash('message', 'Status actualizado');
            return redirect()->back()->with('type', 'success');
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
        }
    }

    public function changePassword($id)
    {
        $res = $this->handleChangePassword('Customer', $id);

        if (isset($res['PWD'])) {
            $password = $res['PWD'];
            Session::flash('message', "La nueva contraseña del Cliente es: <b>$password</b>");
            Session::flash('message-passw', $password);
        } else {
            Session::flash('msg-error', 'Ha ocurrido un error: ' . $res['Error']);
        }
        return redirect()->back();
    }
}
