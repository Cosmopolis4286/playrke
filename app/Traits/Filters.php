<?php

namespace App\Traits;

use App\Models\TimeZone;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

trait Filters
{
    private function getTimeZone()
    {
        return TimeZone::getById(User::getValueById(Auth::id(), 'time_zone'));
    }

    protected function applyDateFilter($date, $query, $column = 'created_at')
    {
        // Permitir cambio de zona horaria
        $timeZone = 'America/Caracas'; // O usar $this->getTimeZone() cuando esté disponible

        // Usar métodos de Carbon para obtener fechas en la zona horaria específica
        switch ($date) {
            case 'today':
                $query->whereDate($column, Carbon::today($timeZone));
                break;
            case 'yesterday':
                $query->whereDate($column, Carbon::yesterday($timeZone));
                break;
            case 'tomorrow':
                $query->whereDate($column, Carbon::tomorrow($timeZone));
                break;
            default:
                // Aquí podrías lanzar una excepción o simplemente no aplicar ningún filtro
                break;
        }

        return $query;
    }

    protected function existsCoin($query, $coinId, $dateFilter = '')
    {
        // Aplicar el filtro de fecha a la consulta
        $query = $this->applyDateFilter($dateFilter, $query);

        // Obtener el listado de monedas filtradas
        $coins = $query->pluck('coin')->toArray();

        // Retornar la primera moneda disponible o el coinId original
        return in_array($coinId, $coins) ? $coinId : ($coins[0] ?? $coinId);
    }

    public function handleViewData($viewData, $userType = '')
    {
        // Manjador de Informacio de Vista
        if (isset($viewData['error'])) {
            if ($userType === 'customer') {
                return response()->json(['Error' => $viewData['error']], 404);
            } else {
                return redirect($viewData['redirect'] ?? '/');
            }
        } elseif (isset($viewData['redirect'])) {
            return redirect($viewData['redirect'] ?? '/');
        }

        return view($viewData['route'])->with($viewData['data']);
    }
}
