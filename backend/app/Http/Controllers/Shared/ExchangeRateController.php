<?php

namespace App\Http\Controllers\Shared;

// Base Controller
use App\Http\Controllers\Controller;

// Models
use App\Models\Currencies\Currency;
use App\Models\Payments\ExchangeRate\Users\Roles\CommerceExchangeRate;
use App\Models\Users\Roles\Customer\Customer;
use App\Models\Payments\ExchangeRate\Users\Roles\CustomerExchangeRate;
use App\Models\Payments\ExchangeRate\ExchangeRate;

// Services
use App\Services\ExchangeRate\ExchangeRateService;

// Traits
use App\Traits\Users\UserTrait;

// Laravel
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ExchangeRateController extends Controller
{
    use UserTrait;

    public function index($creatorType, $creatorId = '')
    {
        $this->getExchangeRates(true);

        switch ($creatorType) {
            case 'customer': //customer

                if ($creatorId) {
                    $customer = Customer::getValueByToken($creatorId, 'id');
                    if (!$customer) {
                        return response()->json(['message' => 'Token del cliente no existe'], 404);
                    }

                    $returnBtn = false;
                    $isLogin = false;
                } else {
                    $customer = Customer::getByUserId(Auth::id(), ['id', 'token']);
                    $isLogin = true;

                    $creatorId = $customer->id;
                }

                $exchangeRate = CustomerExchangeRate::getAvailableExchangeRates($creatorId);
                break;
            case 'commerce_id':
                $commerce = $this->commerce(['id', 'user']);

                $creatorId = $commerce->id;

                $commerceCurrencies = CommerceExchangeRate::getCurrenciesByCommerce($creatorId);

                $this->checkBankDirectoriesExistence($commerceCurrencies);

                $array = array_filter($this->getAvailableCurrencies($commerce->user), fn($value) => $value !== 1);

                $currencies = Currency::whereIn('id', $array)
                    ->whereDoesntHave('CommerceExchangeRate', function ($query) use ($creatorId) {
                        $query->where('commerce_id', $creatorId);
                    })->get(['id', 'symbol', 'description']);


                $exchangeRates = CommerceExchangeRate::getAvailableExchangeRates($creatorId);
                $isLogin = true;
                break;

            default:
                return response()->json(['message' => 'Ruta no encontrada'], 404);
                break;
        }

        $viewData = [
            'layout' => $isLogin ? 'layouts.layout-online' : 'layouts.layout-offline',
            'title' => 'Tasas de Cambio',
            'name' => 'exchange-rate',
            'returnBtn' => $returnBtn ?? true,
            'creator' => ['type' => $creatorType, 'id' => encrypt($creatorId)],
            'currencies' => $currencies ?? [],
            'exchangeRates' => $exchangeRates
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
            $creatorId = decrypt($creator->id);
            $currency = $request->currency;

            if ($creator->type === 'customer') {

                $user = Customer::getValueById($creatorId, 'user');
                $currencyId = CustomerExchangeRate::getCustomerCurrencyValue($creatorId, $currency, 'id');

                $data = [
                    'customer' => $creatorId,
                    'currency' => $currency,
                    'status' => 1,
                    'user' => $user ?? null,
                    'exchange_rate' => $request->exchange_rate
                ];

                if ($currencyId) {
                    $data['status'] = 1;
                    CustomerExchangeRate::where('id', $currencyId)->update($data);
                } else {
                    CustomerExchangeRate::create($data);
                }
            } else {
                $existsCommerceExchangeRate = CommerceExchangeRate::getCommerceCurrencyValue($creatorId, $currency);

                if ($existsCommerceExchangeRate) return redirect()->back();

                $status = $this->getPaymentDetailsByUserId(Auth::id(), $currency) ? 1 : 2;

                CommerceExchangeRate::create([
                    'commerce_id' => $creatorId,
                    'currency' => $currency,
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
                case 'commerce_id':
                    // Buscar el ultimo registro de CommerceExchangeRate por su ID desencriptado
                    $dataExchangeRate = CommerceExchangeRate::findOrFail($exchangeRateID);

                    $pct = $dataExchangeRate->exchange_rate != 0
                        ? round((($newExchangeRate * 100) / $dataExchangeRate->exchange_rate) - 100, 2)
                        : 0;

                    CommerceExchangeRate::create([
                        'commerce_id' => $dataExchangeRate->commerce,
                        'currency' => $dataExchangeRate->currency,
                        'exchange_rate' => $newExchangeRate,
                        'percentage' => $pct,
                        'status' => 'ACTIVE'
                    ]);

                    $dataExchangeRate->status = 'OLD';
                    $dataExchangeRate->save();
                    break;
                case 'customer':
                    // Buscar el ultimo registro de CustomerExchangeRate por su ID desencriptado
                    $dataExchangeRate = CustomerExchangeRate::findOrFail($exchangeRateID);

                    $pct = $dataExchangeRate->exchange_rate != 0
                        ? round((($newExchangeRate * 100) / $dataExchangeRate->exchange_rate) - 100, 2)
                        : 0;

                    CustomerExchangeRate::create([
                        'commerce_id' => $dataExchangeRate->commerce,
                        'currency' => $dataExchangeRate->currency,
                        'exchange_rate' => $newExchangeRate,
                        'percentage' => $pct,
                        'status' => 'ACTIVE'
                    ]);

                    $dataExchangeRate->status = 'OLD';
                    $dataExchangeRate->save();
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

    private function checkExchangeRate($currency, $getAVG = false, $amount = '')
    {
        if ($getAVG) {
            try {
                // Obtener el valor promedio de la tasa con respecto a la moneda recibida
                $exchangeRates = CommerceExchangeRate::getValuesByCurrency($currency);

                if ($exchangeRates->isEmpty()) {
                    // Si no hay tasas de cambio disponibles, no hacer nada y retornar false
                    return false;
                }

                // Calcular el promedio solo si hay más de una tasa, si no usar la única tasa disponible
                // En otra version, divides el bote entre la cantidad de cartones para obtener la tasa
                // con respecto a todas las tasas de cambio de los comercios
                $averageExchangeRate = $exchangeRates->count() > 1 ? $exchangeRates->avg() : $exchangeRates->first();

                ExchangeRate::updateOrCreate(
                    ['currency' => $currency],
                    ['value' => $averageExchangeRate]
                );

                return true;
            } catch (\Exception $e) {
                // Registrar el error
                Log::error('Error actualizando la tasa de cambio: ' . $e->getMessage());
                return false;
            }
        }
    }

    public function delete($data)
    {
        $data = json_decode(urldecode($data), true);
        $exchangeRateID = decrypt($data['id']);

        switch ($data['type']) {
            case 'commerce_id':
                CommerceExchangeRate::where('id', $exchangeRateID)->update(['status' => 2]);
                break;
            case 'customer':
                CustomerExchangeRate::where('id', $exchangeRateID)->update(['status' => 2]);
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
                case 'commerce_id':
                    // Obtener el estado del comercio
                    $exchangeStatus = CommerceExchangeRate::getValueById($exchangeRateID, 'status');

                    // Determinar el estado basado en si está inactiva o no
                    $status = $exchangeStatus == 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

                    CommerceExchangeRate::where('id', $exchangeRateID)->update(['status' => $status]);
                    break;
                case 'customer':
                    // Obtener el estado de la tasa de cambio del cliente
                    $exchangeStatus = CustomerExchangeRate::getValueById($exchangeRateID, 'status');

                    // Determinar el estado basado en si está inactiva o no
                    $status = $exchangeStatus == 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

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
            DB::rollBack();
            Log::error("Error al cambiar el estado de la tasa de cambio: {$th->getMessage()}");
        }
    }

    public function getInfo($data)
    {
        $data = json_decode(urldecode($data), true);
        $creatorType = $data['creatorType'];

        if (!$creatorType) return false;

        $exchangeRateID = decrypt($data['id']);

        switch ($creatorType) {
            case 'commerce_id':
                $info = CommerceExchangeRate::getById($exchangeRateID, ['currency', 'exchange_rate']);
                break;
            case 'customer':
                $info = CustomerExchangeRate::getById($exchangeRateID, ['currency', 'exchange_rate']);
                break;

            default:
                # code...
                break;
        }

        return $info;
    }

    public function getLastValueByCurrency($currency)
    {
        return ExchangeRate::getLastValueByCurrency($currency) ?? 1;
    }

    public function historical($creatorType, $creatorId, $currency)
    {
        $isLogin = true;
        $returnBtn = false;

        switch ($creatorType) {
            case 'customer':
                $creatorId = decrypt($creatorId);
                $currency = decrypt($currency);

                $customer = Customer::getValueByToken($creatorId, 'id');

                if ($customer) {
                    $isLogin = false;
                    $returnBtn = false;
                    $creatorId = $customer;
                }

                $exchangeRate = CustomerExchangeRate::getAllExchangeRatesByCurrency($creatorId, $currency);
                break;
            case 'commerce_id':
                $creatorId = decrypt($creatorId);
                $currency = decrypt($currency);

                $exchangeRate = CommerceExchangeRate::getAllExchangeRatesByCurrency($creatorId, $currency);
                break;
            default:
                return response()->json(['message' => 'Ruta no encontrada'], 404);
                break;
        }

        $viewData = [
            'layout' => $isLogin ? 'layouts.layout-online' : 'layouts.layout-offline',
            'title' => 'Histórico Tasa de Cambio',
            'name' => 'exchange-rate-historical',
            'returnBtn' => $returnBtn ?? true,
            'creator' => ['type' => $creatorType, 'id' => $creatorId],
            'currencies' => $currencies ?? [],
            'exchangeRate' => $exchangeRate
        ];

        return view('modules.all.exchange-rate.historical')->with($viewData);
    }

    public function getExchangeRates($checkUpdate = false)
    {
        $res = app(ExchangeRateService::class)->updateExchangeRates(Auth::id(), $checkUpdate);

        if ($checkUpdate) return;

        if (isset($res['Error'])) {
            return response()->json(['type' => 'warning', 'message' => 'Ha ocurrido un error']);
        } else {
            return response()->json(['type' => 'success', 'message' => 'Proceso exitoso']);
        }
    }
}
