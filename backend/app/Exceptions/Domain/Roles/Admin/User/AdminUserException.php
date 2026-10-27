<?php

namespace App\Exceptions\Domain\Roles\Admin\User;

use App\Exceptions\Domain\DomainException;

final class AdminUserException extends DomainException
{
    public static function alreadyDeleted() : self
    {
        return new self('ADMIN_USER_ALREADY_DELETED', 409);
    }

    public static function cannotAssignAdminRole() : self
    {
        return new self('ADMIN_USER_CANNOT_ASSIGN_ADMIN_ROLE', 403);
    }

    public static function cannotModifyOtherAdmin() : self
    {
        return new self('ADMIN_USER_CANNOT_MODIFY_OTHER_ADMIN', 403);
    }

    public static function cannotModifySelf() : self
    {
        return new self('ADMIN_USER_CANNOT_MODIFY_SELF', 403);
    }

    public static function cannotModifySuperadmin() : self
    {
        return new self('ADMIN_USER_CANNOT_MODIFY_SUPERADMIN', 403);
    }

    public static function deleteFailed() : self
    {
        return new self('ADMIN_USER_DELETE_FAILED', 422);
    }

    public static function invalidStatusChange() : self
    {
        return new self('ADMIN_USER_INVALID_STATUS_CHANGE', 409);
    }

    public static function invalidWalletAmount() : self
    {
        return new self('ADMIN_USER_INVALID_WALLET_AMOUNT', 422);
    }

    public static function invalidXpAmount() : self
    {
        return new self('ADMIN_USER_INVALID_XP_AMOUNT', 422);
    }

    public static function notFound() : self
    {
        return new self('ADMIN_USER_NOT_FOUND', 404);
    }

    public static function passwordResetRequiresActiveStatus() : self
    {
        return new self('ADMIN_USER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS', 422);
    }

    public static function walletLoadRequiresActiveStatus() : self
    {
        return new self('ADMIN_USER_WALLET_LOAD_REQUIRES_ACTIVE_STATUS', 422);
    }

    public static function walletLoadRequiresEligibleRole() : self
    {
        return new self('ADMIN_USER_WALLET_LOAD_REQUIRES_ELIGIBLE_ROLE', 422);
    }

    public static function xpGrantRequiresPlayerRole() : self
    {
        return new self('ADMIN_USER_XP_GRANT_REQUIRES_PLAYER_ROLE', 422);
    }
}
