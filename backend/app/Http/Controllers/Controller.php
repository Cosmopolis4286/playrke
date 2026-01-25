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
 * Controlador base que proporciona métodos auxiliares para manejo centralizado
 * de excepciones, logging y respuestas comunes (web, JSON y Inertia).
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Ejecuta una acción con manejo centralizado de excepciones y logging.
     *
     * @template TReturn
     *
     * @param callable(): TReturn $callback Acción principal a ejecutar.
     * @param string $errorMessage Mensaje para registrar en logs y mostrar en respuesta.
     * @param callable(Throwable): mixed $onError Callback para manejar el error capturado.
     *
     * @return TReturn|mixed Resultado del callback o resultado del manejador de errores.
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
     * En caso de error realiza redirect back con mensaje de error en sesión.
     * Si el callback retorna false, se considera como error y se redirige con mensaje.
     *
     * @param callable $callback Acción a ejecutar que retorna la respuesta válida o bool.
     * @param string $errorMessage Mensaje a mostrar en caso de error.
     *
     * @return mixed|RedirectResponse Resultado del callback o redirección con error.
     */
    protected function handleService(callable $callback, string $errorMessage)
    {
        return $this->executeSafely(
            function () use ($callback, $errorMessage) {
                $result = $callback();

                if ($result === false) {
                    throw new \RuntimeException($errorMessage);
                }

                return $result;
            },
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
     * En caso de éxito responde con:
     * - { success: true } si retorna true,
     * - { success: true, ...datos } si retorna array,
     * En error responde con { success: false, message: errorMessage } y código HTTP definido.
     * Si el callback retorna false se considera error.
     *
     * @param callable $callback Acción a ejecutar que retorna bool|array|null.
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
            function () use ($callback) {
                $result = $callback();

                if ($result === false) {
                    throw new \RuntimeException('Operación fallida');
                }

                if ($result === true) {
                    return response()->json(['success' => true]);
                }

                return response()->json(['success' => true, ...($result ?? [])]);
            },
            $errorMessage,
            fn() => response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], $statusCode)
        );
    }

    /**
     * Maneja un servicio Inertia con renderizado y manejo centralizado de errores.
     *
     * En caso de éxito retorna la vista Inertia.
     * En error renderiza el componente Inertia definido mostrando el mensaje de error.
     * Si el callback retorna false se considera error.
     *
     * @param callable(): InertiaResponse|bool $callback Acción que retorna la vista Inertia o bool.
     * @param string $errorMessage Mensaje para mostrar en caso de error.
     * @param string $errorComponent Nombre del componente Inertia para mostrar en errores (por defecto 'ErrorPage').
     *
     * @return InertiaResponse Respuesta Inertia válida.
     */
    protected function handleServiceInertia(
        callable $callback,
        string $errorMessage,
        string $errorComponent = 'ErrorPage'
    ): InertiaResponse {
        return $this->executeSafely(
            function () use ($callback, $errorMessage, $errorComponent) {
                $result = $callback();

                if ($result === false) {
                    throw new \RuntimeException($errorMessage);
                }

                return $result;
            },
            $errorMessage,
            fn() => Inertia::render($errorComponent, [
                'message' => $errorMessage,
            ])->status(500)
        );
    }
}
