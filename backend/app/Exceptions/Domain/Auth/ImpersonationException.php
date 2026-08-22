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
        return new self('ALREADY_IMPERSONATING');
    }

    public static function targetMustBePlayer(): self
    {
        return new self('IMPERSONATION_TARGET_MUST_BE_PLAYER');
    }

    public static function notImpersonating(): self
    {
        return new self('NOT_IMPERSONATING');
    }
}
