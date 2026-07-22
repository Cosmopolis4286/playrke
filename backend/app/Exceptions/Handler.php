<?php

namespace App\Exceptions;

use App\Exceptions\Domain\DomainException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Excepciones que no deben reportarse.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        DomainException::class,
    ];

    /**
     * Renderizado centralizado de excepciones.
     */
    public function render($request, Throwable $exception): Response
    {
        if ($exception instanceof DomainException) {
            return $this->renderDomainException($request, $exception);
        }

        // Para requests AJAX/JSON, siempre responder con JSON
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage() ?: 'Error interno del servidor.',
            ], $this->getStatusCode($exception));
        }

        // Renderizar errores HTTP via Inertia para requests de navegación
        $status = $this->getStatusCode($exception);

        if (in_array($status, [403, 404, 500, 503], strict: true)) {
            return Inertia::render('Errors/Error', [
                'status'  => $status,
                'message' => $exception->getMessage() ?: null,
                'url'     => $request->fullUrl(),
            ])->toResponse($request)->setStatusCode($status);
        }

        return parent::render($request, $exception);
    }

    /**
     * Obtiene el HTTP status code apropiado para la excepción.
     */
    protected function getStatusCode(Throwable $exception): int
    {
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }

        return match (true) {
            $exception instanceof \Illuminate\Auth\AuthenticationException => 401,
            $exception instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
            $exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 404,
            $exception instanceof \Illuminate\Validation\ValidationException => 422,
            default => 500,
        };
    }

    /**
     * Renderiza excepciones de dominio.
     *
     * @param Request $request
     * @param DomainException $exception
     * @return JsonResponse
     */
    protected function renderDomainException(
        Request $request,
        DomainException $exception
    ): JsonResponse {
        $errorCode = $exception->getMessage();

        return response()->json([
            'success'    => false,
            'error_code' => $errorCode,
            'message'    => $this->resolveMessage($errorCode),
        ], $this->resolveStatusCode($errorCode));
    }

    /**
     * Mapea error codes de dominio a HTTP status codes.
     *
     * @param string $errorCode
     * @return int
     */
    protected function resolveStatusCode(string $errorCode): int
    {
        return match ($errorCode) {
            // Not Found
            'ADMIN_COMMERCE_NOT_FOUND',
            'ADMIN_USER_NOT_FOUND'
            => Response::HTTP_NOT_FOUND,

            // Forbidden / Authorization
            'ADMIN_USER_CANNOT_MODIFY_SELF',
            'ADMIN_USER_CANNOT_MODIFY_SUPERADMIN'
            => Response::HTTP_FORBIDDEN,

            // Conflicts / Invalid state
            'ADMIN_COMMERCE_ALREADY_DELETED',
            'ADMIN_COMMERCE_INVALID_STATUS_CHANGE',
            'ADMIN_COMMERCE_DESTROY_NOT_ALLOWED',
            'ADMIN_USER_ALREADY_DELETED',
            'ADMIN_USER_INVALID_STATUS_CHANGE'
            => Response::HTTP_CONFLICT,

            // Validation / Business rules
            'ADMIN_COMMERCE_CREATION_FAILED',
            'ADMIN_COMMERCE_UPDATE_FAILED',
            'ADMIN_COMMERCE_DELETE_FAILED',
            'ADMIN_COMMERCE_PASSWORD_RESET_FAILED',
            'ADMIN_USER_DELETE_FAILED',
            'ADMIN_USER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS',
            'ADMIN_USER_WALLET_LOAD_REQUIRES_ACTIVE_STATUS',
            'ADMIN_USER_INVALID_WALLET_AMOUNT',
            'ADMIN_USER_INVALID_XP_AMOUNT',
            'ADMIN_USER_XP_GRANT_REQUIRES_PLAYER_ROLE'
            => Response::HTTP_UNPROCESSABLE_ENTITY,

            // Fallback
            default => Response::HTTP_BAD_REQUEST,
        };
    }

    /**
     * Resuelve el mensaje traducido del error.
     *
     * @param string $errorCode
     * @return string
     */
    protected function resolveMessage(string $errorCode): string
    {
        return trans("errors.{$errorCode}", [], app()->getLocale())
            ?: $errorCode;
    }
}
