<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Laravel\Reverb\Loggers\Log;
use Symfony\Component\HttpFoundation\Session\Session;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    /**
     * Manejo centralizado de errores para los métodos del Controller.
     *
     * @param callable $callback
     * @param string $errorMessage
     * @return mixed
     */
    function handleService(callable $callback, string $errorMessage)
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            Log::error("❌ $errorMessage Error: " . $e->getMessage());
            Session::flash('error', $errorMessage);

            return redirect()->back()->withErrors(['error' => $errorMessage]);
        }
    }
}
