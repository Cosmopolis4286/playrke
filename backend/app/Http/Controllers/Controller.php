<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Facades\Log;
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
    /* Core executor                                                          */
    /* ====================================================================== */

    /**
     * Ejecuta una acción y delega errores al Handler global.
     *
     * @template T
     * @param callable(): T $callback
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
    /* Web                                                                    */
    /* ====================================================================== */

    /**
     * Maneja servicios web tradicionales (redirect).
     *
     * @param callable $callback
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
    /* JSON / AJAX                                                           */
    /* ====================================================================== */

    /**
     * Maneja servicios JSON con estructura estándar.
     *
     * @param callable $callback
     * @return JsonResponse
     *
     * @throws Throwable
     */
    protected function handleServiceJson(callable $callback): JsonResponse
    {
        return response()->json([
            'success' => true,
            ...((array) $this->execute($callback)),
        ]);
    }

    /* ====================================================================== */
    /* Inertia                                                               */
    /* ====================================================================== */

    /**
     * Maneja servicios Inertia.
     *
     * @param callable(): InertiaResponse $callback
     * @return InertiaResponse
     */
    protected function handleServiceInertia(callable $callback): InertiaResponse
    {
        return $this->execute($callback);
    }

    /* ====================================================================== */
    /* Logging                                                               */
    /* ====================================================================== */

    /**
     * Logging centralizado de errores.
     *
     * @param Throwable $exception
     * @return void
     */
    protected function logError(Throwable $exception): void
    {
        Log::error($exception->getMessage(), [
            'exception' => $exception,
            'trace'     => app()->isLocal()
                ? $exception->getTraceAsString()
                : null,
        ]);
    }
}
