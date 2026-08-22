<?php

/**
 * Translated messages for domain error codes (DomainException).
 *
 * Handler::resolveMessage() resolves "errors.{errorCode}" against this
 * file; if a key is missing here, the raw error code is shown to the
 * user (previous behavior for this whole exception tree).
 */
return [
    // Users (admin/user)
    'ADMIN_USER_NOT_FOUND' => 'User not found.',
    'ADMIN_USER_ALREADY_DELETED' => 'The user has already been deleted.',
    'ADMIN_USER_INVALID_STATUS_CHANGE' => 'This user\'s status cannot be changed.',
    'ADMIN_USER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Only active users can have their password reset.',
    'ADMIN_USER_WALLET_LOAD_REQUIRES_ACTIVE_STATUS' => 'Only active users can be credited balance.',
    'ADMIN_USER_WALLET_LOAD_REQUIRES_ELIGIBLE_ROLE' => 'Balance can only be credited to users with the Commerce, Seller, or Player role.',
    'ADMIN_USER_INVALID_WALLET_AMOUNT' => 'The entered amount is not valid.',
    'ADMIN_USER_INVALID_XP_AMOUNT' => 'The entered XP amount is not valid.',
    'ADMIN_USER_XP_GRANT_REQUIRES_PLAYER_ROLE' => 'XP can only be granted to users with the Player role.',
    'ADMIN_USER_DELETE_FAILED' => 'The user could not be deleted.',
    'ADMIN_USER_CANNOT_MODIFY_SELF' => 'You cannot perform this action on your own account.',
    'ADMIN_USER_CANNOT_MODIFY_SUPERADMIN' => 'You do not have permission to modify a Super Administrator account.',

    // Administrators (admin/administrator)
    'ADMINISTRATOR_NOT_FOUND' => 'Administrator not found.',
    'ADMINISTRATOR_NOT_ADMINISTRATOR' => 'The user is not an administrator.',
    'ADMINISTRATOR_ALREADY_DELETED' => 'The administrator has already been deleted.',
    'ADMINISTRATOR_INVALID_STATUS_CHANGE' => 'This administrator\'s status cannot be changed.',
    'ADMINISTRATOR_CANNOT_DELETE_LAST_SUPERADMIN' => 'The last SuperAdmin in the system cannot be deleted.',
    'ADMINISTRATOR_CANNOT_MODIFY_SELF' => 'You cannot perform this action on your own administrator account.',
    'ADMINISTRATOR_CANNOT_REMOVE_LAST_ACTIVE' => 'The last active administrator in the system cannot be deactivated/deleted.',
    'ADMINISTRATOR_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Only active administrators can have their password reset.',

    // Commerces (admin/commerce)
    'ADMIN_COMMERCE_NOT_FOUND' => 'Commerce not found.',
    'ADMIN_COMMERCE_ALREADY_DELETED' => 'The commerce has already been deleted.',
    'ADMIN_COMMERCE_INVALID_STATUS_CHANGE' => 'This commerce\'s status cannot be changed.',
    'ADMIN_COMMERCE_DESTROY_NOT_ALLOWED' => 'This commerce cannot be permanently deleted.',
    'ADMIN_COMMERCE_CREATION_FAILED' => 'The commerce could not be created.',
    'ADMIN_COMMERCE_UPDATE_FAILED' => 'The commerce could not be updated.',
    'ADMIN_COMMERCE_DELETE_FAILED' => 'The commerce could not be deleted.',
    'ADMIN_COMMERCE_PASSWORD_RESET_FAILED' => 'The commerce\'s password could not be reset.',
    'ADMIN_COMMERCE_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Only active commerces can have their password reset.',

    // Customers (admin/customer)
    'ADMIN_CUSTOMER_NOT_FOUND' => 'Customer not found.',
    'ADMIN_CUSTOMER_ALREADY_DELETED' => 'The customer has already been deleted.',
    'ADMIN_CUSTOMER_INVALID_STATUS_CHANGE' => 'This customer\'s status cannot be changed.',
    'ADMIN_CUSTOMER_DESTROY_NOT_ALLOWED' => 'This customer cannot be permanently deleted.',
    'ADMIN_CUSTOMER_CREATION_FAILED' => 'The customer could not be created.',
    'ADMIN_CUSTOMER_UPDATE_FAILED' => 'The customer could not be updated.',
    'ADMIN_CUSTOMER_DELETE_FAILED' => 'The customer could not be deleted.',
    'ADMIN_CUSTOMER_PASSWORD_RESET_FAILED' => 'The customer\'s password could not be reset.',
    'ADMIN_CUSTOMER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Only active customers can have their password reset.',

    // Players (admin/player)
    'ADMIN_PLAYER_NOT_FOUND' => 'Player not found.',
    'ADMIN_PLAYER_ALREADY_DELETED' => 'The player has already been deleted.',
    'ADMIN_PLAYER_INVALID_STATUS_CHANGE' => 'This player\'s status cannot be changed.',
    'ADMIN_PLAYER_CREATION_FAILED' => 'The player could not be created.',
    'ADMIN_PLAYER_UPDATE_FAILED' => 'The player could not be updated.',
    'ADMIN_PLAYER_DELETE_FAILED' => 'The player could not be deleted.',
    'ADMIN_PLAYER_PASSWORD_RESET_FAILED' => 'The player\'s password could not be reset.',
    'ADMIN_PLAYER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Only active players can have their password reset.',

    // Treasurers (admin/treasurer)
    'ADMIN_TREASURER_NOT_FOUND' => 'Treasurer not found.',
    'ADMIN_TREASURER_ALREADY_DELETED' => 'The treasurer has already been deleted.',
    'ADMIN_TREASURER_INVALID_STATUS_CHANGE' => 'This treasurer\'s status cannot be changed.',
    'ADMIN_TREASURER_DESTROY_NOT_ALLOWED' => 'This treasurer cannot be permanently deleted.',
    'ADMIN_TREASURER_CREATION_FAILED' => 'The treasurer could not be created.',
    'ADMIN_TREASURER_UPDATE_FAILED' => 'The treasurer could not be updated.',
    'ADMIN_TREASURER_DELETE_FAILED' => 'The treasurer could not be deleted.',
    'ADMIN_TREASURER_PASSWORD_RESET_FAILED' => 'The treasurer\'s password could not be reset.',
    'ADMIN_TREASURER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Only active treasurers can have their password reset.',

    // Sellers managed by Manager (seller/manager)
    'MANAG_SELLER_NOT_FOUND' => 'Seller not found.',
    'MANAG_SELLER_ALREADY_DELETED' => 'The seller has already been deleted.',
    'MANAG_SELLER_INVALID_STATUS_CHANGE' => 'This seller\'s status cannot be changed.',
    'MANAG_SELLER_DESTROY_NOT_ALLOWED' => 'This seller cannot be permanently deleted.',
    'MANAG_SELLER_CREATION_FAILED' => 'The seller could not be created.',
    'MANAG_SELLER_UPDATE_FAILED' => 'The seller could not be updated.',
    'MANAG_SELLER_DELETE_FAILED' => 'The seller could not be deleted.',
    'MANAG_SELLER_PASSWORD_RESET_FAILED' => 'The seller\'s password could not be reset.',
    'MANAG_SELLER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Only active sellers can have their password reset.',

    // Impersonation (player support)
    'ALREADY_IMPERSONATING' => 'There is already an active support session. End it before starting another.',
    'IMPERSONATION_TARGET_MUST_BE_PLAYER' => 'Only users with the Player role can be impersonated.',
    'NOT_IMPERSONATING' => 'There is no active support session to end.',
];
