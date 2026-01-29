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
 * - Excepciones
 * - Logging
 * - Respuestas Web, JSON e Inertia
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /* ====================================================================== */
    /* Core executor                                                          */
    /* ====================================================================== */

    /**
     * Ejecuta una acción con manejo centralizado de excepciones.
     *
     * @template T
     *
     * @param callable(): T $callback
     * @param string $errorMessage
     * @param callable(Throwable): mixed $onError
     *
     * @return T|mixed
     */
    protected function executeSafely(
        callable $callback,
        string $errorMessage,
        callable $onError
    ) {
        try {
            return $callback();
        } catch (Throwable $e) {
            Log::error($errorMessage, [
                'exception' => $e,
            ]);

            try {
                return $onError($e);
            } catch (Throwable $inner) {
                Log::critical('Error en el handler de error', [
                    'exception' => $inner,
                ]);

                abort(500, 'Error interno del servidor.');
            }
        }
    }

    /* ====================================================================== */
    /* Web                                                                     */
    /* ====================================================================== */

    /**
     * Maneja servicios web tradicionales (redirect + flash errors).
     *
     * @param callable $callback
     * @param string $errorMessage
     *
     * @return mixed|RedirectResponse
     */
    protected function handleService(callable $callback, string $errorMessage)
    {
        return $this->executeSafely(
            fn () => $this->ensureSuccess($callback(), $errorMessage),
            $errorMessage,
            fn () => redirect()
                ->back()
                ->withErrors(['error' => $errorMessage])
                ->with('error', $errorMessage)
        );
    }

    /* ====================================================================== */
    /* JSON / AJAX                                                            */
    /* ====================================================================== */

    /**
     * Maneja servicios JSON con estructura estándar.
     *
     * @param callable $callback
     * @param string $errorMessage
     * @param int $statusCode
     *
     * @return JsonResponse
     */
    protected function handleServiceJson(
        callable $callback,
        string $errorMessage,
        int $statusCode = 500
    ): JsonResponse {
        return $this->executeSafely(
            fn () => $this->jsonSuccess(
                $this->ensureSuccess($callback(), $errorMessage)
            ),
            $errorMessage,
            fn () => $this->jsonFail($errorMessage, $statusCode)
        );
    }

    /* ====================================================================== */
    /* Inertia                                                                */
    /* ====================================================================== */

    /**
     * Maneja servicios Inertia con fallback de error.
     *
     * @param callable(): InertiaResponse|bool $callback
     * @param string $errorMessage
     * @param string $errorComponent
     *
     * @return InertiaResponse
     */
    protected function handleServiceInertia(
        callable $callback,
        string $errorMessage,
        string $errorComponent = 'ErrorPage'
    ): InertiaResponse {
        return $this->executeSafely(
            fn () => $this->ensureSuccess($callback(), $errorMessage),
            $errorMessage,
            fn () => Inertia::render($errorComponent, [
                'message' => $errorMessage,
            ])->status(500)
        );
    }

    /* ====================================================================== */
    /* Helpers                                                                */
    /* ====================================================================== */

    /**
     * Valida que el resultado sea exitoso.
     *
     * @param mixed $result
     * @param string $errorMessage
     * @return mixed
     */
    protected function ensureSuccess(mixed $result, string $errorMessage): mixed
    {
        if ($result === false) {
            throw new \RuntimeException($errorMessage);
        }

        return $result;
    }

    /**
     * Respuesta JSON de éxito.
     *
     * @param bool|array|null $data
     * @return JsonResponse
     */
    protected function jsonSuccess(bool|array|null $data = true): JsonResponse
    {
        if ($data === true || $data === null) {
            return response()->json(['success' => true]);
        }

        return response()->json([
            'success' => true,
            ...$data,
        ]);
    }

    /**
     * Respuesta JSON de error.
     *
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function jsonFail(string $message, int $statusCode = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }
}
