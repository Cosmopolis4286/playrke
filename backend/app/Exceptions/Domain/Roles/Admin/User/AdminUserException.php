<?php

namespace App\Exceptions\Domain\Roles\Admin\User;

use App\Exceptions\Domain\DomainException;

final class AdminUserException extends DomainException
{
    public static function notFound(): self
    {
        return new self('ADMIN_USER_NOT_FOUND');
    }

    public static function alreadyDeleted(): self
    {
        return new self('ADMIN_USER_ALREADY_DELETED');
    }

    public static function invalidStatusChange(): self
    {
        return new self('ADMIN_USER_INVALID_STATUS_CHANGE');
    }

    public static function passwordResetRequiresActiveStatus(): self
    {
        return new self('ADMIN_USER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS');
    }

    public static function walletLoadRequiresActiveStatus(): self
    {
        return new self('ADMIN_USER_WALLET_LOAD_REQUIRES_ACTIVE_STATUS');
    }

    public static function invalidWalletAmount(): self
    {
        return new self('ADMIN_USER_INVALID_WALLET_AMOUNT');
    }

    public static function invalidXpAmount(): self
    {
        return new self('ADMIN_USER_INVALID_XP_AMOUNT');
    }

    public static function deleteFailed(): self
    {
        return new self('ADMIN_USER_DELETE_FAILED');
    }

    public static function cannotModifySelf(): self
    {
        return new self('ADMIN_USER_CANNOT_MODIFY_SELF');
    }

    public static function cannotModifySuperadmin(): self
    {
        return new self('ADMIN_USER_CANNOT_MODIFY_SUPERADMIN');
    }
}
