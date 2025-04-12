<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Games;
use App\Models\Lottery;
use App\Models\User;
use App\Models\UserType;
use App\Traits\Filters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LotteryController extends Controller
{
    use Filters;

    public function index($userToken = false)
    {
        if ($userToken) {
            $userType = 'customer';
            $userToken = true;
            $user = Customer::getValueByToken($userToken, 'user');
        } else {
            $user = Auth::id();
            $userTypeID = User::getValueById($user, 'user_type');
            $userType = UserType::getValueById($userTypeID, 'prefix');
        }

        if (!in_array($userType, ['admin', 'commerce', 'customer'])) {
            return redirect()->back();
        }

        $lotteries = Lottery::all();

        $viewData = [
            'layout' => !$userToken ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Loterias',
            'userToken' => $userToken,
            'name' => 'lottery',
            'lotteries' => $lotteries
        ];

        $viewData = ['route' => 'modules.game.lottery.index', 'data' => $viewData];

        return $this->handleViewData($viewData, $userType);
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
            ])->id;

            // Crear un nuevo Wallet para el usuario
            Wallet::create(['coin' => $request->coin, 'user' => $newUser]);

            $phoneNumber = $request->phone;
            PhoneNumbers::create([
                'user' => $newUser,
                'country' => $request->country,
                'phone_number' => $phoneNumber
            ]);

            $commerce = Commerce::create([
                'name' => $request->name,
                'phone' => $phoneNumber,
                'coin' => $request->coin,
                'country' => $request->country,
                'status' => 1,
                'user' => $newUser,
                'code_rke' => $this->replaceLettersWithNumbers($codeRKE),
                'code_uuid' => $this->getCodeUUID('Commerce')
            ]);

            // Registar moneda Principal
            CommerceCountriesCoins::create([
                'commerce' => $commerce->id,
                'country' => $commerce->country,
                'coin' => $commerce->coin,
                'creator_user' => $newUser,
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
                        'creator_user' => $newUser,
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
                'user' => $newUser,
                'user_type' => UserType::getValueByPrefix('commerce', 'id'),
            ]);

            DB::commit();

            // Mensajes flash con tiempo de expiración personalizado
            Session::flash('message', 'Registro exitoso');
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

                $otherCoins = $request->other_coins;
                $this->insertOtherCoins($commerce->id, $commerce->user, $otherCoins);

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
}
