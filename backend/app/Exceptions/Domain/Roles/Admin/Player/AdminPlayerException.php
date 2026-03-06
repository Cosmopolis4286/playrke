<?php

namespace App\Exceptions\Domain\Roles\Admin\Player;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio para el agregado Player en contexto Admin.
 */
final class AdminPlayerException extends DomainException
{
    public static function creationFailed(): self
    {
        return new self('ADMIN_PLAYER_CREATION_FAILED');
    }

    public static function updateFailed(): self
    {
        return new self('ADMIN_PLAYER_UPDATE_FAILED');
    }

    public static function deleteFailed(): self
    {
        return new self('ADMIN_PLAYER_DELETE_FAILED');
    }

    public static function alreadyDeleted(): self
    {
        return new self('ADMIN_PLAYER_ALREADY_DELETED');
    }

    public static function invalidStatusChange(): self
    {
        return new self('ADMIN_PLAYER_INVALID_STATUS_CHANGE');
    }

    public static function notFound(): self
    {
        return new self('ADMIN_PLAYER_NOT_FOUND');
    }

    public static function passwordResetFailed(): self
    {
        return new self('ADMIN_PLAYER_PASSWORD_RESET_FAILED');
    }

    public static function passwordResetRequiresActiveStatus(): self
    {
        return new self('ADMIN_PLAYER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS');
    }
}
