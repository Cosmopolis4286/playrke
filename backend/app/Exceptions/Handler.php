<?php

namespace App\Exceptions;

use App\Exceptions\Domain\DomainException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        return parent::render($request, $exception);
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
            'ADMIN_COMMERCE_NOT_FOUND' => Response::HTTP_NOT_FOUND,

            // Conflicts / Invalid state
            'ADMIN_COMMERCE_ALREADY_DELETED',
            'ADMIN_COMMERCE_INVALID_STATUS_CHANGE',
            'ADMIN_COMMERCE_DESTROY_NOT_ALLOWED'
            => Response::HTTP_CONFLICT,

            // Validation / Business rules
            'ADMIN_COMMERCE_CREATION_FAILED',
            'ADMIN_COMMERCE_UPDATE_FAILED',
            'ADMIN_COMMERCE_DELETE_FAILED',
            'ADMIN_COMMERCE_PASSWORD_RESET_FAILED'
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
