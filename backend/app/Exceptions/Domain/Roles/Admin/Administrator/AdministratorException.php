<?php

namespace App\Exceptions\Domain\Roles\Admin\Administrator;

use App\Exceptions\Domain\DomainException;

final class AdministratorException extends DomainException
{
    public static function notAdministrator(): self
    {
        return new self('ADMINISTRATOR_NOT_ADMINISTRATOR', 403);
    }

    public static function invalidStatusChange(): self
    {
        return new self('ADMINISTRATOR_INVALID_STATUS_CHANGE', 409);
    }

    public static function alreadyDeleted(): self
    {
        return new self('ADMINISTRATOR_ALREADY_DELETED', 409);
    }

    public static function cannotDeleteLastSuperAdmin(): self
    {
        return new self('ADMINISTRATOR_CANNOT_DELETE_LAST_SUPERADMIN', 409);
    }

    public static function notFound(): self
    {
        return new self('ADMINISTRATOR_NOT_FOUND', 404);
    }

    public static function cannotModifySelf(): self
    {
        return new self('ADMINISTRATOR_CANNOT_MODIFY_SELF', 403);
    }

    public static function cannotRemoveLastActiveAdministrator(): self
    {
        return new self('ADMINISTRATOR_CANNOT_REMOVE_LAST_ACTIVE', 409);
    }

    public static function passwordResetRequiresActiveStatus(): self
    {
        return new self('ADMINISTRATOR_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS', 422);
    }
}
