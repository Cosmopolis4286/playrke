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
        //$this->getTimeZone(); En una proxima version permitir cambio de zona horaria
        date_default_timezone_set('America/Caracas');

        // Usar métodos de Carbon para obtener fechas
        if ($date === 'today') {
            $query->whereDate($column, Carbon::today());
        } elseif ($date === 'yesterday') {
            $query->whereDate($column, Carbon::yesterday());
        } elseif ($date === 'tomorrow') {
            $query->whereDate($column, Carbon::tomorrow());
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
}
