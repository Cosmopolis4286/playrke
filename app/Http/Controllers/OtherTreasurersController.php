<?php

namespace App\Http\Controllers;

use App\Traits\EncryptRKE;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\OtherTreasurers;
use App\Models\Treasurer;
use App\Models\TreasurerCountriesCoins;
use App\Models\PhoneNumbers;
use App\Traits\Filters;
use App\Traits\RouteRKE;
use App\Traits\UserVal;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OtherTreasurersController extends Controller
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
}
