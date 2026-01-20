<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Throwable;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Ejecuta una acción con manejo centralizado de excepciones y logging.
     *
     * @template TReturn
     *
     * @param callable(): TReturn $callback Acción principal a ejecutar.
     * @param string $errorMessage Mensaje de error para log y respuesta.
     * @param callable(Throwable): mixed $onError Callback para manejo de error.
     *
     * @return TReturn|mixed Resultado del callback o del manejo de error.
     */
    protected function executeSafely(
        callable $callback,
        string $errorMessage,
        callable $onError
    ) {
        try {
            return $callback();
        } catch (Throwable $e) {
            // Registro explícito con mensaje y traza para mejor debugging
            Log::error($errorMessage, [
                'exception_message' => $e->getMessage(),
                'exception_trace' => $e->getTraceAsString(),
            ]);

            try {
                return $onError($e);
            } catch (Throwable $inner) {
                Log::critical('Error en el handler de error: ' . $inner->getMessage(), [
                    'exception_trace' => $inner->getTraceAsString(),
                ]);

                abort(500, 'Error interno del servidor.');
            }
        }
    }

    /**
     * Maneja un servicio web tradicional con redirección y manejo de errores.
     *
     * @param callable $callback Acción a ejecutar.
     * @param string $errorMessage Mensaje para mostrar en caso de error.
     *
     * @return mixed|RedirectResponse Resultado del callback o redirect con error.
     */
    protected function handleService(callable $callback, string $errorMessage)
    {
        return $this->executeSafely(
            $callback,
            $errorMessage,
            fn(Throwable $e) => redirect()
                ->back()
                ->withErrors(['error' => $errorMessage])
                ->with('error', $errorMessage)
        );
    }

    /**
     * Maneja un servicio JSON/AJAX con respuesta estructurada y manejo de errores.
     *
     * @param callable $callback Acción a ejecutar que devuelve un array o null.
     * @param string $errorMessage Mensaje para respuesta en caso de error.
     * @param int $statusCode Código HTTP para respuesta de error.
     *
     * @return JsonResponse Respuesta JSON con resultado o mensaje de error.
     */
    protected function handleServiceJson(
        callable $callback,
        string $errorMessage,
        int $statusCode = 500
    ): JsonResponse {
        return $this->executeSafely(
            fn() => response()->json([
                'success' => true,
                ...($callback() ?? []),
            ]),
            $errorMessage,
            fn() => response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], $statusCode)
        );
    }
}
