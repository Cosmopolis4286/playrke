<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerUser;
use App\Models\PhoneNumbers;
use App\Models\User;
use App\Models\Role;
use App\Models\UniqueCode;
use App\Models\Wallet;
use App\Traits\Games\SeamlessWallet;
use App\Traits\Helpers\EncryptTrait;
use App\Traits\Users\UserValTrait;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    use EncryptTrait, UserValTrait, SeamlessWallet;

    private function validateRequest($request, $type = 1, $id = '')
    {
        // Si es tipo 2, encuentra al usuario usando el ID descifrado.
        if ($type == 2) {
            $customer = Customer::findOrFail(decrypt($id));
        }

        // Definir reglas de validación.
        $rules = [
            'username' => ['required', 'string', 'max:20', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:40', 'unique:users,email'],
            'customer_id' => ['required', 'string', 'max:30', 'unique:customers,customer_id'],
            'country' => ['required', 'numeric', 'exists:countries,id'],
            'time_zone' => ['required', 'numeric', 'exists:time_zones,id'],
            'name' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:customers,phone'],
            'api_route' => ['required', 'string', 'max:255'],
            'customer_key' => ['required', 'string', 'max:255'],
        ];

        // Ajustar reglas de validación si es una actualización.
        if ($type == 2) {
            $rules['username'] = ['required', 'string', 'max:20', 'unique:users,username,' . $customer->user];
            $rules['email'] = ['required', 'string', 'email', 'max:40', 'unique:users,email,' . $customer->user];
            $rules['customer_id'] = ['required', 'string', 'max:30', 'unique:customers,customer_id,' . $customer->id];
            $rules['phone'] = ['nullable', 'string', 'max:20', 'unique:customers,phone,' . $customer->id];
        }

        // Definir mensajes de error personalizados.
        $messages = [
            'username.unique' => 'Nombre de usuario no disponible.',
            'email.unique' => 'Este correo ya está registrado.',
            'customer_id.unique' => 'Cliente ID ya está registrado.',
            'phone.unique' => 'Este celular (' . $request->phone . ') ya está registrado.',
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

    private function getEncryptionKeyForClient($customerKey)
    {
        // Crear clave de Encriptacion para Clave de Conexion del Cliente
        $securityKey = $this->generateBase64Key();
        $customerKey = encrypt($customerKey, $securityKey);

        $securityKey = encrypt($securityKey, env('CTMER_ENCRYPTION_KEY'));

        return ['customerKey' => $customerKey, 'securityKey' => $securityKey];
    }

    private function saveLogo(UploadedFile $logo, $customerID)
    {
        try {
            if ($logo && $logo->isValid()) {
                // Crear un nombre único para el archivo
                $fileName = Carbon::now()->timestamp . '.' . $logo->getClientOriginalExtension();

                // Crear una carpeta personalizada usando el ID del cliente
                $customerFolder = "customers/$customerID";

                // Eliminar cualquier archivo existente en la carpeta
                Storage::disk('public')->deleteDirectory($customerFolder);

                // Guardar el nuevo archivo en la carpeta específica
                $logo->storeAs($customerFolder, $fileName, 'public');

                return $fileName;
            }

            throw new \Exception('El archivo no es válido o está corrupto.');
        } catch (\Exception $e) {
            // Registrar el error para depuración
            Log::error("Error al guardar el logo para el cliente $customerID: " . $e->getMessage());

            throw new HttpResponseException(response()->json([
                'message' => 'Error al guardar el logo. Inténtalo de nuevo.',
                'error' => $e->getMessage()
            ], 500));
        }
    }

    public function create(Request $request) /// Registar Comercializadoras
    {
        if ($this->user(['user_type'])->user_type != 2) {
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
                'user_type' => Role::getValueByPrefix('customer', 'id'),
                'email' => $request->email,
                'password' => Hash::make($newPassw),
                'country' => $request->country,
                'time_zone' => $request->time_zone,
                'email_verified_at' => Carbon::now(),
                'referral_code' => User::getUniqueReferralCode(),
            ])->id;

            // Crear un nuevo Wallet para el usuario
            Wallet::create(['coin' => $request->coin ?? 1, 'user' => $newUser]);

            $phoneNumber = $request->phone;

            if ($phoneNumber) {
                PhoneNumbers::create([
                    'user' => $newUser,
                    'country' => $request->country,
                    'phone_number' => $phoneNumber
                ]);
            }

            $keys = $this->getEncryptionKeyForClient($request->customer_key);

            $codeUUID = $this->getCodeUUID();

            $fileLogo = $request->file('logo');
            $logo = $fileLogo ? $this->saveLogo($fileLogo, $codeUUID) : null;

            Customer::create([
                'name' => $request->name,
                'country' => $request->country,
                'phone' => $phoneNumber,
                'customer_id' => $request->customer_id,
                'user' => $newUser,
                'logo' => $logo,
                'link_route' => $request->api_route,
                'token' => $codeUUID,
                'customer_key' => $keys['customerKey'],
                'security_key' => $keys['securityKey'],
                'status' => 1
            ]);

            // Registrar Code UUID
            UniqueCode::create([
                'uuid_code' => $codeUUID,
                'user' => $newUser,
                'user_type' => Role::getValueByPrefix('customer', 'id')
            ]);

            DB::commit();

            // Mensajes flash con tiempo de expiración personalizado
            Session::flash('message', "Registro exitoso");
            Session::flash('pawss', $newPassw);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear Customer: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Hubo un error al procesar la solicitud.']);
        }

        return redirect()->back();
    }

    public function getInfo($id) // Obtener Info Comercializadora
    {
        $id = decrypt($id);

        // Obtener datos del cliente
        $customer = Customer::getById($id, ['name', 'customer_id', 'country', 'phone', 'link_route']);

        // Obtener datos del usuario
        $user = User::getById(Customer::getValueById($id, 'user'), ['username', 'email', 'time_zone']);

        $keys = Customer::getById($id, ['customer_key', 'security_key']);
        // Desencriptar clave de seguridad 
        $encryptedKey = decrypt($keys->security_key, env('CTMER_ENCRYPTION_KEY'));
        // Desencriptar la clave de seguridad del cliente usando la clave específica
        $customerKey = decrypt($keys->customer_key, $encryptedKey);

        // Anexar el customerKey al array de customer
        $customerData = array_merge($customer->toArray(), ['customer_key' => $customerKey]);

        $data = [
            'customer' => $customerData,
            'user' => $user
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
            // Decryptar el ID y encontrar el cliente
            $customer = Customer::findOrFail(decrypt($id));

            if ($customer) {

                $validator = $this->validateRequest($request, 2, $id);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator)->withInput();
                }

                $userID = $customer->user;
                $userTimeZone = User::getValueById($userID, 'time_zone');

                if ($userTimeZone <> $request->time_zone) {
                    User::where('id', $userID)->update(['time_zone' => $request->time_zone]);
                }

                $fileLogo = $request->file('logo');
                $logo = $fileLogo ? $this->saveLogo($fileLogo, $customer->token) : null;

                $phoneNumber = $request->phone;

                if ($phoneNumber <> $customer->phone) {
                    PhoneNumbers::where('user', $customer->user)->update([
                        'country' => $request->country,
                        'phone_number' => $phoneNumber
                    ]);
                }

                $data = [
                    'name' => $request->name,
                    'country' => $request->country,
                    'time_zone' => $request->time_zone,
                    'customer_id' => $request->customer_id,
                    'phone' => $phoneNumber,
                    'logo' => $logo,
                    'link_route' => $request->api_route
                ];

                // Desencriptar clave de seguridad 
                $encryptedKey = decrypt($customer->security_key, env('CTMER_ENCRYPTION_KEY'));
                // Desencriptar la clave de seguridad del cliente usando la clave específica
                $customerKey = decrypt($customer->customer_key, $encryptedKey);

                if ($customerKey != $request->customer_key) {
                    $keys = $this->getEncryptionKeyForClient($request->customer_key);
                    $data['customer_key'] = $keys['customerKey'];
                    $data['security_key'] = $keys['securityKey'];
                }

                // Actualizar los campos del cliente
                $customer->update($data);

                DB::commit();

                Session::flash('message', 'La información fue actualizada correctamente');
            } else {
                // Manejar caso donde no se encuentra el cliente
                Session::flash('msg-error', 'No se encontró el cliente');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            // Captura cualquier excepción lanzada durante el proceso de actualización
            Session::flash('msg-error', 'Hubo un problema al actualizar la información del cliente: ' . $e->getMessage());
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
            Session::flash('pawss', $password);
        } else {
            Session::flash('msg-error', 'Ha ocurrido un error: ' . $res['Error']);
        }
        return redirect()->back();
    }

    public function getUserId($userToken)
    {
        $customerID = Customer::getValueByUserId(Auth::id(), 'id');
        if (!$customerID) return false;
        return $this->getUserInfoFromCustomer($customerID, $userToken);
    }

    public function getUserBalance($userToken)
    {
        $customerID = Customer::getValueByUserId(Auth::id(), 'id');
        if (!$customerID) return false;
        return $this->getUserBalanceFromCustomer($customerID, $userToken);
    }

    public function increaseBalance($userToken, $balance = 2000)
    {
        $customerID = Customer::getValueByUserId(Auth::id(), 'id');
        if (!$customerID) return false;

        $player = CustomerUser::getByToken($customerID, $userToken, ['id', 'coin']);

        if (!$player) {
            return response()->json(['type' => 'warning', 'message' => 'Usuario no encontrado en DB']);
        }

        DB::beginTransaction();

        try {
            $transaction = $this->registerTransaction(
                ['name' => 'BINGO', 'game_mode' => 1],
                $customerID,
                $player->id,
                'TESTE_RECARGAR_SALDO',
                'WIN',
                $player->coin,
                $balance
            );

            $response = $this->transferMoney($transaction, 'TESTE_RECARGAR_SALDO');

            if (isset($response['Error']) && $response['Error']) {
                $typeMessage = 'warning';
                $message = 'Error al recargar saldo';
                Log::error($message . ': ' . $response['Error']);
            } else {
                $typeMessage = 'success';
                $message = 'Recarga de saldo exitosa';
                Log::info($message . ' Monto recargado: ' . $balance);

                // Actualizar la transacción como exitosa
                $transaction->update([
                    'status' => 1,
                    'processing_data' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();
            }
            return response()->json(['type' => $typeMessage, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['type' => 'warning', 'message' => 'Ha ocurrido un error']);
        }
    }
}
