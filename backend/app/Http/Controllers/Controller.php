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
     * Ejecuta una acción encapsulando el manejo de excepciones.
     *
     * Este método NO debe ser llamado directamente.
     * Es el núcleo compartido por handlers Web y JSON.
     *
     * @template TReturn
     *
     * @param callable(): TReturn $callback
     * @param string $errorMessage
     * @param callable(Throwable): mixed $onError
     *
     * @return TReturn|mixed
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

            return $onError($e);
        }
    }

    /**
     * Manejo centralizado de errores para Controllers Web (redirect).
     *
     * @param callable $callback
     * @param string $errorMessage
     *
     * @return mixed|RedirectResponse
     */
    protected function handleService(
        callable $callback,
        string $errorMessage
    ) {
        return $this->executeSafely(
            $callback,
            $errorMessage,
            function () use ($errorMessage): RedirectResponse {
                Session::flash('error', $errorMessage);

                return redirect()
                    ->back()
                    ->withErrors(['error' => $errorMessage]);
            }
        );
    }

    /**
     * Manejo centralizado de errores para endpoints JSON / AJAX.
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
            function () use ($callback): JsonResponse {
                $data = $callback();

                return response()->json([
                    'success' => true,
                    ...($data ?? []),
                ]);
            },
            $errorMessage,
            function () use ($errorMessage, $statusCode): JsonResponse {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], $statusCode);
            }
        );
    }
}
