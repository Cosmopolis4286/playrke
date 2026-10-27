<?php

namespace App\Traits\Helpers;

use App\Models\TimeZone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

trait FiltersTrait
{
    public function handleViewData($viewData, $creatorType = '')
    {
        // Manjador de Informacio de Vista
        if (isset($viewData['error'])) {
            if ($creatorType === 'customer') {
                return response()->json(['Error' => $viewData['error']], 404);
            }

            return redirect($viewData['redirect'] ?? '/');
        }
        if (isset($viewData['redirect'])) {
            if ($viewData['redirect'] === 'redirect-back') {
                return redirect()->back();
            }

            return redirect($viewData['redirect'] ?? '/');
        }

        return view($viewData['route'])->with($viewData['data']);
    }

    protected function applyDateFilter($date, $query, $column = 'created_at')
    {
        // Permitir cambio de zona horaria
        $timeZone = config('app.user_timezone', 'UTC');

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

    private function getTimeZone()
    {
        return TimeZone::getById(User::getValueById(Auth::id(), 'time_zone'));
    }
}
