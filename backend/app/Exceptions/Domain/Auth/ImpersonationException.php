<?php

namespace App\Exceptions\Domain\Auth;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio para App\Services\Auth\ImpersonationService.
 */
final class ImpersonationException extends DomainException
{
    public static function alreadyImpersonating(): self
    {
        return new self('ALREADY_IMPERSONATING', 409);
    }

    public static function targetRoleNotAllowed(): self
    {
        return new self('IMPERSONATION_TARGET_ROLE_NOT_ALLOWED', 422);
    }

    public static function notImpersonating(): self
    {
        return new self('NOT_IMPERSONATING', 409);
    }
}
