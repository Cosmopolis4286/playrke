<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Models\Commerce;
use App\Models\CommerceExchangeRate;
use App\Models\Customer;
use App\Models\CustomerExchangeRate;
use App\Models\ExchangeRate;
use App\Traits\UserVal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ExchangeRateController extends Controller
{
    use UserVal;

    public function index($userType, $userID = '')
    {
        switch ($userType) {
            case 'customer': //customer
                $customer = Customer::getValueByToken($userID, 'id');

                if (!$customer) return response()->json(['message' => 'Token del cliente no existe'], 404);

                $exchangeRate = CustomerExchangeRate::where('customer', $customer)->where('status', '<>', 3)->get();

                $returnBtn = false;

                $moreViewData = ['layout' => 'layouts.app_rke_off', 'cusToken' => $userID];
                break;
            case 'commerce':
                $commerce = $this->commerce(['id', 'user']);
                $commerceID = $commerce->id;

                $coins = Coin::whereIn('id', $this->getAvailableCoins($commerce->user))
                    ->whereDoesntHave('CommerceExchangeRate', function ($query) use ($commerceID) {
                        $query->where('commerce', $commerceID);
                    })->get(['id', 'symbol', 'long_name']);

                $commerceCoins = CommerceExchangeRate::getCoinsByCommerce($commerceID, 1);

                $coinsNotDirectories = $this->checkBankDirectoriesExistence($commerce->user, $commerceCoins);

                if (count($coinsNotDirectories)) {
                    $this->changeStatusByExchangeRateWithoutDirectories($coinsNotDirectories, $commerceID);
                }

                $exchangeRate = CommerceExchangeRate::where('commerce', $commerceID)->get();

                $moreViewData = ['layout' => 'layouts.app_rke', 'wallet' => $this->wallet()];
                break;

            default:
                return response()->json(['message' => 'Ruta no encontrada'], 404);
                break;
        }

        $viewData = [
            'title' => 'Tasas de Cambio',
            'name' => 'currency',
            'returnBtn' => $returnBtn ?? true,
            'userType' => $userType,
            'coins' => $coins ?? [],
            'exchangeRate' => $exchangeRate
        ];

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return view('modules.all.exchange-rate.index')->with($viewData);
    }

    public function create(Request $request)
    {
        DB::beginTransaction();

        try {

            $currency = $request->currency;

            if ($currency) {

                $customer = Customer::getByToken($request->cust_token, ['id', 'user']);

                $currencyID = CustomerExchangeRate::getCustomerCoinValue($customer->id, $currency, 'id');

                if (!$currencyID) {
                    CustomerExchangeRate::create([
                        'customer' => $customer->id,
                        'coin' => $currency,
                        'exchange_rate' => $request->val_change,
                        'status' => 1,
                        'user' => $customer->user ?? null
                    ]);
                } else {
                    CustomerExchangeRate::where('id', $currencyID)->update(['status' => 1]);
                }
            } else {
                $commerce = Commerce::getByUserId(Auth::id(), ['id', 'user']);
                $coin = $request->coin;

                $existsCommerceExchangeRate = CommerceExchangeRate::getCommerceCoinValue($commerce->id, $coin);

                if ($existsCommerceExchangeRate) return redirect()->back();

                $status = $this->getPaymentDetailsByUserId($commerce->user, $coin) ? 1 : 2;

                CommerceExchangeRate::create([
                    'commerce' => $commerce->id,
                    'coin' => $coin,
                    'exchange_rate' => $request->val_change,
                    'status' => $status,
                    'user' => Auth::id()
                ]);
            }

            DB::commit();

            return redirect()->back()->with('message', 'La tasa de cambio ha sido cargada con exito');
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
            Log::error('Error al registar la tasa de cambio: ' . $e->getMessage());
            return redirect()->back()->with('message', 'Error al registar la tasa de cambio')->with('type', 'warning');
        }

        // DB::beginTransaction();

        // try {
        //     $this->checkExchangeRate($coin);
        //     DB::commit();
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     Log::error('Error al actualizar la tasa de cambio general');
        // }
    }

    public function update(Request $request, $data)
    {
        $data = json_decode(urldecode($data), true);
        $exchangeRateID = decrypt($data[0]);

        DB::beginTransaction();

        try {

            $exchangeRate = $request->val_change;

            switch ($data[1]) {
                case 'commerce':
                    // Buscar el registro de CommerceExchangeRate por su ID desencriptado
                    $commerceExchangeRate = CommerceExchangeRate::findOrFail($exchangeRateID);

                    //$commerceExchangeRate->coin = $coin;
                    $commerceExchangeRate->exchange_rate = $exchangeRate;
                    $commerceExchangeRate->save();
                    break;
                case 'customer':
                    // Buscar el registro de CustomerExchangeRate por su ID desencriptado
                    $customerExchangeRate = CustomerExchangeRate::findOrFail($exchangeRateID);

                    //$customerExchangeRate->coin = $coin;
                    $customerExchangeRate->exchange_rate = $exchangeRate;
                    $customerExchangeRate->save();
                    break;
                default:
                    # code...
                    break;
            }

            DB::commit();

            return redirect()->back()->with('message', 'La información fue actualizada con éxito');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error actualizando la tasa de cambio: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error al actualizar la información');
        }
    }

    private function checkExchangeRate($coin, $getAVG = false, $amount = '')
    {
        if ($getAVG) {
            try {
                // Obtener el valor promedio de la tasa con respecto a la moneda recibida
                $exchangeRates = CommerceExchangeRate::getValuesByCoin($coin);

                if ($exchangeRates->isEmpty()) {
                    // Si no hay tasas de cambio disponibles, no hacer nada y retornar false
                    return false;
                }

                // Calcular el promedio solo si hay más de una tasa, si no usar la única tasa disponible
                // En otra version, divides el bote entre la cantidad de cartones para obtener la tasa
                // con respecto a todas las tasas de cambio de los comercios 
                $averageExchangeRate = $exchangeRates->count() > 1 ? $exchangeRates->avg() : $exchangeRates->first();

                ExchangeRate::updateOrCreate(
                    ['coin' => $coin],
                    ['value' => $averageExchangeRate]
                );

                return true;
            } catch (\Exception $e) {
                // Registrar el error
                Log::error('Error actualizando la tasa de cambio: ' . $e->getMessage());
                return false;
            }
        }

        // $checkVal = ExchangeRate::getLastValueByCoin($coin);
        // $valChange = $amount;

        // if ($checkVal) {
        //     // Ajustar $valChange si está fuera del rango permitido
        //     $valChange = max($amount, $checkVal - 3); // Mantener $valChange como mínimo igual a $checkVal - 3
        //     $valChange = min($valChange, $checkVal + 2); // Mantener $valChange como máximo igual a $checkVal + 2
        // }
        // return number_format($valChange, 2, '.', '');
    }

    public function delete($data)
    {
        $data = json_decode(urldecode($data), true);
        $exchangeRateID = decrypt($data[0]);

        switch ($data[1]) {
            case 'commerce':
                CommerceExchangeRate::where('id', $exchangeRateID)->update(['status' => 3]);
                break;
            case 'customer':
                CustomerExchangeRate::where('id', $exchangeRateID)->update(['status' => 3]);
                break;

            default:
                # code...
                break;
        }
        return redirect()->back()->with('message', 'La tasa de cambio fue eliminada');
    }

    public function changeStatus($data)
    {
        $data = json_decode(urldecode($data), true);
        $exchangeRateID = decrypt($data[0]);

        DB::beginTransaction();

        try {
            switch ($data[1]) {
                case 'commerce':
                    // Obtener el estado del comercio
                    $commerceStatus = CommerceExchangeRate::getValueById($exchangeRateID, 'status');

                    // Determinar si el comercio está eliminado
                    $isDeleted = $commerceStatus == 3 ? true : false;

                    // Determinar el estado basado en si está eliminado o no
                    $status = $isDeleted ? 1 : (int)!$commerceStatus;

                    CommerceExchangeRate::where('id', $exchangeRateID)->update(['status' => $status]);
                    break;
                case 'customer':
                    // Obtener el estado del cliente
                    $customerStatus = CustomerExchangeRate::getValueById($exchangeRateID, 'status');

                    // Determinar si el cliente está eliminado
                    $isDeleted = $customerStatus == 3 ? true : false;

                    // Determinar el estado basado en si está eliminado o no
                    $status = $isDeleted ? 1 : (int)!$customerStatus;

                    CustomerExchangeRate::where('id', $exchangeRateID)->update(['status' => $status]);
                    break;

                default:
                    # code...
                    break;
            }

            DB::commit();

            Session::flash('message', 'Status actualizado');
            return redirect()->back()->with('type', 'success');
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            dd($th->getMessage());
        }
    }

    public function getInfo($data)
    {
        $data = json_decode(urldecode($data), true);
        $exchangeRateID = decrypt($data[0]);

        switch ($data[1]) {
            case 'commerce':
                $info = CommerceExchangeRate::getById($exchangeRateID, ['coin', 'exchange_rate']);
                break;
            case 'customer':
                $info = CustomerExchangeRate::getById($exchangeRateID, ['coin', 'exchange_rate']);
                break;

            default:
                # code...
                break;
        }

        return $info;
    }

    public function getLastValueByCoin($coin)
    {
        return ExchangeRate::getLastValueByCoin($coin) ?? 1;
    }

    public function changeStatusByExchangeRateWithoutDirectories($coins, $user)
    {
        DB::beginTransaction();

        try {
            if (count($coins)) {
                // status => 2 Sin directorio registrado
                CommerceExchangeRate::where('commerce', $user)->whereIn('coin', $coins)->update(['status' => 2]);
            } else {
                CommerceExchangeRate::where('commerce', $user)->update(['status' => 1]);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error al cambiar el estado de las monedas sin directorios: ' . $th->getMessage());
        }
    }
}
