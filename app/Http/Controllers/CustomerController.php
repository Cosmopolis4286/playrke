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
            $customer = Customer::findOrFail(decrypt($id));
        }

        // Definir reglas de validación.
        $rules = [
            'username' => ['required', 'string', 'max:20', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:40', 'unique:users,email'],
            'country' => ['required', 'numeric', 'exists:countries,id'],
            'name' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:20', 'unique:customers,phone'],
            'api_route' => ['required', 'string', 'max:255'],
            'customer_key' => ['required', 'string', 'max:255'],
        ];

        // Ajustar reglas de validación si es una actualización.
        if ($type == 2) {
            $rules['username'] = ['required', 'string', 'max:20', 'unique:users,username,' . $customer->user];
            $rules['email'] = ['required', 'string', 'email', 'max:40', 'unique:users,email,' . $customer->user];
            $rules['phone'] = ['required', 'string', 'max:20', 'unique:customers,phone,' . $customer->id];
        }

        // Definir mensajes de error personalizados.
        $messages = [
            'username.unique' => 'Nombre de usuario no disponible.',
            'email.unique' => 'Este correo ya está registrado.',
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

    private function saveLogo($logo)
    {
        // Guardar logo del cliente si existe y crear carpeta personalizada 
    }

    public function create(Request $request) /// Registar Comercializadoras
    {
        if (!in_array($this->user(['user_type']), [2])) {
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
                'user_type' => UserType::getValueByPrefix('customer', 'id'),
                'email' => $request->email,
                'password' => Hash::make($newPassw),
                'country' => $request->country,
                'referral_code' => User::getUniqueReferralCode(),
            ]);

            // Crear un nuevo Wallet para el usuario
            Wallet::create(['coin' => $request->coin ?? 1, 'user' => $newUser->id]);

            $keys = $this->getEncryptionKeyForClient($request->customer_key);

            $logo = $request->logo ? $this->saveLogo($request->logo) : null;

            $customerToken = Customer::create([
                'name' => $request->name,
                'country' => $request->country,
                'phone' => $request->phone,
                'user' => $newUser->id,
                'logo' => $logo,
                'link_route' => $request->api_route,
                'token' => $this->getCodeUUID(),
                'customer_key' => $keys['customerKey'],
                'security_key' => $keys['securityKey'],
                'status' => 1
            ])->token;

            // Registrar Code UUID
            UUIDCodes::create([
                'code_uuid' => $customerToken,
                'user_type' => UserType::getValueByPrefix('customer', 'id')
            ]);

            DB::commit();

            // Mensajes flash con tiempo de expiración personalizado
            Session::flash('message', "Cliente $request->name registrado con éxito");
            Session::flash('message-passw', $newPassw);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error general: ' . $e->getMessage());
            dd($e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Hubo un error al procesar la solicitud.']);
        }

        return redirect()->back();
    }

    public function getInfo($id) // Obtener Info Comercializadora
    {
        $id = decrypt($id);

        // Obtener datos del cliente
        $customer = Customer::getById($id, ['name', 'country', 'phone', 'link_route']);

        // Obtener datos del usuario
        $user = User::getById(Customer::getValueById($id, 'user'), ['username', 'email']);

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
        if (!in_array($this->user(['user_type']), [2])) {
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

                $logo = $request->logo ? $this->saveLogo($request->logo) : null;

                $data = [
                    'name' => $request->name,
                    'country' => $request->country,
                    'phone' => $request->phone,
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
            Session::flash('message-passw', $password);
        } else {
            Session::flash('msg-error', 'Ha ocurrido un error: ' . $res['Error']);
        }
        return redirect()->back();
    }
}
