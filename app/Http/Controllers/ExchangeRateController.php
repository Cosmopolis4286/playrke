<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Models\Commerce;
use App\Models\CommerceExchangeRate;
use App\Models\ExchangeRate;
use App\Traits\UserVal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExchangeRateController extends Controller
{
    use UserVal;

    protected $idCommerceRKE;

    public function __construct()
    {
        $this->idCommerceRKE = 1;
    }

    public function index()
    {
        $title = 'Tasas de Cambio';
        $name = 'currency';
        $wallet = $this->Wallet(Auth::id());
        $commerce = $this->commerce()->id;

        $coins = Coin::whereNot('id', 1)->get(['id', 'symbol', 'long_name']);

        // //with('CommerceExchangeRate')
        // ->whereHas('CommerceExchangeRate', function ($query) {
        //     $query->whereColumn('coin', 'commerces_countries_coins.coin')
        //         ->where('commerce', $this->commerce()->id);
        // }) /

        $commerceExchangeRate = CommerceExchangeRate::with(['Coin.Country'])->where('commerce', $commerce)->get()->unique('coin');

        return view('modules.commerce.currency.index', compact('title', 'name', 'coins', 'wallet', 'CommerceExchangeRate'));
    }

    public function create(Request $request)
    {
        $commerce = Commerce::getByUserId(Auth::id(), ['id', 'user']);
        $coin = $request->coin;
        $existsCommerceExchangeRate = CommerceExchangeRate::getCommerceCoinValue($commerce->id, $coin);

        if ($existsCommerceExchangeRate) return redirect()->back();

        DB::beginTransaction();

        try {
            $valChange = $this->checkExchangeRate($coin, $request->val_change);

            $status = $this->getPaymentDetailsByUserId($commerce->user, $coin) ? 1 : 2;

            CommerceExchangeRate::create([
                'coin' => $coin,
                'value' => $valChange,
                'status' => $status,
                'commerce' => $commerce->id,
                'user' => Auth::id()
            ]);

            $check = $this->checkExchangeRate($coin);
            if (!$check) {
                DB::rollBack();
                Log::error('Error al actualizar la tasa de cambio general');
            }

            DB::commit();

            return redirect()->back()->with('message', 'La moneda ha sido cargada con exito');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registar la tasa de cambio: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Hubo un error al confirmar el pago.']);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $coin = $request->coin;
            $valChange = $this->checkExchangeRate($coin, $request->val_change);

            // Buscar el registro de CommerceExchangeRate por su ID desencriptado
            $commerceExchangeRate = CommerceExchangeRate::findOrFail(decrypt($id));

            // Actualizar los campos necesarios
            $commerceExchangeRate->coin = $coin;
            $commerceExchangeRate->value = $valChange;
            $commerceExchangeRate->save();

            $check = $this->checkExchangeRate($coin);
            if (!$check) {
                DB::rollBack();
                Log::error('Error al actualizar la tasa de cambio general');
            }

            // Completar la transacción
            DB::commit();

            // Redireccionar con un mensaje de éxito
            return redirect()->back()->with('message', 'La información fue actualizada con éxito');
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();

            // Registrar el error
            Log::error('Error actualizando la tasa de cambio: ' . $e->getMessage());

            // Redireccionar con un mensaje de error
            return redirect()->back()->with('error', 'Ocurrió un error al actualizar la información');
        }
    }

    private function checkExchangeRate($coin, $val = '')
    {
        if (!$val) {
            try {
                // Obtener el valor promedio de la tasa con respecto a la moneda recibida
                $exchangeRates = CommerceExchangeRate::getValuesByCoin($coin);

                if ($exchangeRates->isEmpty()) {
                    // Si no hay tasas de cambio disponibles, no hacer nada y retornar false
                    return false;
                }

                // Calcular el promedio solo si hay más de una tasa, si no usar la única tasa disponible
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
        } else {
            $checkVal = ExchangeRate::getLastValueByCoin($coin);
            // Ajustar $valChange si está fuera del rango permitido
            $valChange = max($val, $checkVal - 3); // Mantener $valChange como mínimo igual a $checkVal - 3
            $valChange = min($valChange, $checkVal + 2); // Mantener $valChange como máximo igual a $checkVal + 2

            return number_format($valChange, 2, '.', '');
        }
    }

    public function delete($id)
    {
        //CommerceExchangeRate::destroy(decrypt($id));
        CommerceExchangeRate::where('id', decrypt($id))->update(['status' => 0]);
        return redirect()->back()->with('message', 'La moneda fue eliminada');
    }

    public function getInfo($id)
    {
        return CommerceExchangeRate::getById(decrypt($id), ['coin', 'value']);
    }

    public function getLastValueByCoin($coin)
    {
        return ExchangeRate::getLastValueByCoin($coin) ?? 1;
    }
}
