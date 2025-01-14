<?php

namespace App\Http\Controllers;

use App\Models\Coin;
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

    public function index($creatorType, $creatorID = '')
    {
        switch ($creatorType) {
            case 'customer': //customer

                if ($creatorID) {
                    $customer = Customer::getValueByToken($creatorID, 'id');
                    if (!$customer) {
                        return response()->json(['message' => 'Token del cliente no existe'], 404);
                    }

                    $returnBtn = false;
                    $isLogin = false;
                } else {
                    $customer = Customer::getByUserId(Auth::id(), ['id', 'token']);
                    $isLogin = true;

                    $creatorID = $customer->id;
                }

                $exchangeRate = CustomerExchangeRate::getAvailableExchangeRates($creatorID);
                break;
            case 'commerce':
                $commerce = $this->commerce(['id', 'user']);

                $creatorID = $commerce->id;

                $commerceCoins = CommerceExchangeRate::getCoinsByCommerce($creatorID);

                $this->checkBankDirectoriesExistence('commerce', $commerceCoins);

                $coins = Coin::whereIn('id', $this->getAvailableCoins($commerce->user))
                    ->whereDoesntHave('CommerceExchangeRate', function ($query) use ($creatorID) {
                        $query->where('commerce', $creatorID);
                    })->get(['id', 'symbol', 'long_name']);

                $exchangeRate = CommerceExchangeRate::getAvailableExchangeRates($creatorID);

                $isLogin = true;

                break;

            default:
                return response()->json(['message' => 'Ruta no encontrada'], 404);
                break;
        }

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Tasas de Cambio',
            'name' => 'coin',
            'returnBtn' => $returnBtn ?? true,
            'creator' => ['type' => $creatorType, 'id' => encrypt($creatorID)],
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

            $creator = json_decode($request->creator);
            $creatorID = decrypt($creator->id);
            $coin = $request->coin;

            if ($creator->type === 'customer') {

                $user = Customer::getValueById($creatorID, 'user');
                $coinID = CustomerExchangeRate::getCustomerCoinValue($creatorID, $coin, 'id');

                $data = [
                    'customer' => $creatorID,
                    'coin' => $coin,
                    'status' => 1,
                    'user' => $user ?? null,
                    'exchange_rate' => $request->exchange_rate
                ];

                if ($coinID) {
                    $data['status'] = 1;
                    CustomerExchangeRate::where('id', $coinID)->update($data);
                } else {
                    CustomerExchangeRate::create($data);
                }
            } else {
                $existsCommerceExchangeRate = CommerceExchangeRate::getCommerceCoinValue($creatorID, $coin);

                if ($existsCommerceExchangeRate) return redirect()->back();

                $status = $this->getPaymentDetailsByUserId(Auth::id(), $coin) ? 1 : 2;

                CommerceExchangeRate::create([
                    'commerce' => $creatorID,
                    'coin' => $coin,
                    'exchange_rate' => $request->exchange_rate,
                    'status' => $status,
                    'user' => Auth::id()
                ]);
            }

            DB::commit();

            return redirect()->back()->with('message', 'La tasa de cambio ha sido cargada con exito');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registar la tasa de cambio: ' . $e->getMessage());
            return redirect()->back()->with('message', 'Error al registar la tasa de cambio')->with('type', 'warning');
        }
    }

    public function update(Request $request, $data)
    {
        $data = json_decode(urldecode($data), true);
        $exchangeRateID = decrypt($data['id']);

        DB::beginTransaction();

        try {

            $newExchangeRate = $request->exchange_rate;

            switch ($data['creatorType']) {
                case 'commerce':
                    // Buscar el ultimo registro de CommerceExchangeRate por su ID desencriptado
                    $dataExchangeRate = CommerceExchangeRate::findOrFail($exchangeRateID);

                    $dataExchangeRate->status = 2;
                    $dataExchangeRate->save();

                    $pct = $dataExchangeRate->exchange_rate != 0
                        ? round(($newExchangeRate * 100) / $dataExchangeRate->exchange_rate, 2)
                        : 0;

                    CommerceExchangeRate::create([
                        'commerce' => $dataExchangeRate->commerce,
                        'coin' => $dataExchangeRate->coin,
                        'exchange_rate' => $newExchangeRate,
                        'pct' => $pct,
                        'status' => 1
                    ]);
                    break;
                case 'customer':
                    // Buscar el ultimo registro de CustomerExchangeRate por su ID desencriptado
                    $dataExchangeRate = CustomerExchangeRate::findOrFail($exchangeRateID);

                    $dataExchangeRate->status = 2;
                    $dataExchangeRate->save();

                    $pct = $dataExchangeRate->exchange_rate != 0
                        ? round(($newExchangeRate * 100) / $dataExchangeRate->exchange_rate, 2)
                        : 0;

                    CustomerExchangeRate::create([
                        'commerce' => $dataExchangeRate->commerce,
                        'coin' => $dataExchangeRate->coin,
                        'exchange_rate' => $newExchangeRate,
                        'pct' => $pct,
                        'status' => 1
                    ]);
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
        $exchangeRateID = decrypt($data['id']);

        switch ($data['type']) {
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
        $exchangeRateID = decrypt($data['id']);

        DB::beginTransaction();

        try {
            switch ($data['creatorType']) {
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
        $creatorType = $data['creatorType'];

        if (!$creatorType) return false;

        $exchangeRateID = decrypt($data['id']);

        switch ($creatorType) {
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

    public function historical($coin = 'all', $creatorType, $creatorID = '')
    {
        $viewData = [];

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return view('modules.all.exchange-rate.historical')->with($viewData);
    }
}
