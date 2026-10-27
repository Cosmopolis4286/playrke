<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

/**
 * Controlador base con manejo centralizado de:
 * - Logging
 * - Respuestas Web, JSON e Inertia
 *
 * NO contiene mensajes de error ni lógica de dominio.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /* ====================================================================== */
    /* Core executor */
    /* ====================================================================== */

    /**
     * Ejecuta una acción y delega errores al Handler global.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     *
     * @throws Throwable
     */
    protected function execute(callable $callback)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            $this->logError($e);

            throw $e; // ⬅️ clave: dejar que Handler decida
        }
    }

    /* ====================================================================== */
    /* Web */
    /* ====================================================================== */

    /**
     * Maneja servicios web tradicionales (redirect).
     *
     * @return mixed|RedirectResponse
     */
    protected function handleService(callable $callback)
    {
        try {
            return $this->execute($callback);
        } catch (Throwable) {
            return redirect()->back();
        }
    }

    /* ====================================================================== */
    /* Inertia */
    /* ====================================================================== */

    /**
     * Maneja servicios Inertia.
     *
     * @param callable(): InertiaResponse $callback
     */
    protected function handleServiceInertia(callable $callback) : InertiaResponse
    {
        return $this->execute($callback);
    }

    /* ====================================================================== */
    /* JSON / AJAX */
    /* ====================================================================== */

    /**
     * Maneja servicios JSON con estructura estándar.
     *
     *
     * @throws Throwable
     */
    protected function handleServiceJson(callable $callback) : JsonResponse
    {
        return response()->json([
            'success' => true,
            ...((array) $this->execute($callback)),
        ]);
    }

    /* ====================================================================== */
    /* Logging */
    /* ====================================================================== */

    /**
     * Logging centralizado de errores.
     */
    protected function logError(Throwable $exception) : void
    {
        Log::error($exception->getMessage(), [
            'exception' => $exception,
            'trace' => app()->isLocal()
                ? $exception->getTraceAsString()
                : null,
        ]);
    }
}
