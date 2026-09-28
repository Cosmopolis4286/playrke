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
    'ADMIN_USER_CANNOT_MODIFY_OTHER_ADMIN' => 'Only a Super Administrator can modify another Administrator\'s account.',
    'ADMIN_USER_CANNOT_ASSIGN_ADMIN_ROLE' => 'Only a Super Administrator can assign the Administrator role.',

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
    'ADMIN_COMMERCE_IS_DEFAULT' => 'This commerce is marked as the main one. Reassign the mark to another commerce before blocking or deleting it.',
    'ADMIN_COMMERCE_NOT_DEFAULT' => 'This commerce is no longer marked as the main one — please reload the page.',

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
    'IMPERSONATION_TARGET_ROLE_NOT_ALLOWED' => 'This user role cannot be impersonated.',
    'NOT_IMPERSONATING' => 'There is no active support session to end.',

    // Subsidiaries (admin/subsidiary)
    'ADMIN_SUBSIDIARY_NOT_FOUND' => 'Subsidiary not found.',
    'ADMIN_SUBSIDIARY_ALREADY_DELETED' => 'The subsidiary has already been deleted.',
    'ADMIN_SUBSIDIARY_INVALID_STATUS_CHANGE' => 'This subsidiary\'s status cannot be changed.',
    'ADMIN_SUBSIDIARY_DESTROY_NOT_ALLOWED' => 'This subsidiary cannot be permanently deleted.',
    'ADMIN_SUBSIDIARY_CREATION_FAILED' => 'The subsidiary could not be created.',
    'ADMIN_SUBSIDIARY_UPDATE_FAILED' => 'The subsidiary could not be updated.',
    'ADMIN_SUBSIDIARY_DELETE_FAILED' => 'The subsidiary could not be deleted.',
    'ADMIN_SUBSIDIARY_PASSWORD_RESET_FAILED' => 'The subsidiary\'s password could not be reset.',
    'ADMIN_SUBSIDIARY_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Only active subsidiaries can have their password reset.',

    // Bingo rooms (games/bingo/room)
    'BINGO_ROOM_NOT_FOUND' => 'The bingo room does not exist.',
    'BINGO_ROOM_ALREADY_DELETED' => 'The bingo room has already been deleted.',
    'BINGO_ROOM_INVALID_STATUS_CHANGE' => 'This room\'s status cannot be changed.',
    'BINGO_ROOM_DESTROY_NOT_ALLOWED' => 'You cannot permanently delete a room with player, sale, or prize history.',
    'BINGO_ROOM_CREATION_FAILED' => 'The bingo room could not be created.',
    'BINGO_ROOM_UPDATE_FAILED' => 'The bingo room could not be updated.',
    'BINGO_ROOM_DELETE_FAILED' => 'The bingo room could not be deleted.',
    'BINGO_ROOM_PRIVATE_NOT_VERIFIED' => 'You must verify the access code before buying in this private room.',
    'BINGO_ROOM_INVALID_ROOM_TYPE' => 'The selected room type is not valid.',
    'BINGO_ROOM_INVALID_GAME_MODE' => 'The selected game mode is not valid.',
    'BINGO_ROOM_INVALID_PRIZE_POOL_TYPE' => 'The selected prize pool type is not valid.',
    'BINGO_ROOM_EXPIRED_STATUS_LOCKED' => 'You cannot change the status of a room whose date/time has already expired.',
    'BINGO_ROOM_HAS_PLAYERS' => 'You cannot delete a room that already has registered players.',
    'BINGO_ROOM_ACTIVATION_NOT_ALLOWED' => 'Only rooms without a scheduled time can be activated manually.',
    'BINGO_ROOM_ACTIVATION_FORBIDDEN' => 'You do not have permission to activate this room.',
    'BINGO_ROOM_ACTIVATION_INVALID_STATE' => 'Only rooms in ACTIVE status whose draw has not started can be activated manually.',
    'BINGO_ROOM_SCHEDULE_START_INVALID_DATE' => 'The start date and time must be at least 10 minutes after the current time.',
    'BINGO_ROOM_MIN_CARDS_NOT_REACHED' => 'The room requires at least :required cards sold to start (currently has :current).',
    'BINGO_ROOM_MIN_PLAYERS_NOT_REACHED' => 'The room requires at least :required players to start (currently has :current).',
    'BINGO_ROOM_FULL' => 'The room has reached its maximum player limit.',
    'BINGO_ROOM_CARD_LIMIT_PARTIAL' => 'This room only allows :remaining more card(s) (you requested :requested).',
    'BINGO_ROOM_CARD_LIMIT_EXHAUSTED' => 'This room has already reached its available card limit.',
    'BINGO_ROOM_CURRENCY_REMOVAL_WITH_SALES_NOT_ALLOWED' => 'You cannot remove :currencies: it already has cards sold in this room.',
    'BINGO_ROOM_NOT_EDITABLE_STATUS' => 'You can only edit a room whose draw has not started or finished.',
    'BINGO_ROOM_FIELDS_LOCKED_BY_SALES' => 'This room already has cards sold: :fields cannot be changed.',

    // Bingo patterns (games/bingo/pattern)
    'BINGO_PATTERN_DUPLICATE_NAME' => 'A pattern with that name already exists.',

    // Bingo cards (games/bingo/card)
    'BINGO_CARD_SELLER_NOT_FOUND' => 'Seller not found.',
    'BINGO_CARD_COMMERCE_NOT_FOUND' => 'Commerce not found.',
    'BINGO_CARD_HIERARCHY_NOT_ACTIVE' => 'Your account, agency, or commerce is not active. Contact support for more information.',
    'BINGO_CARD_ROOM_NOT_AVAILABLE' => 'This room is not available for buying cards.',
    'BINGO_CARD_CURRENCY_NOT_AVAILABLE' => 'The selected currency is not available for this room.',
    'BINGO_CARD_GENERATION_FAILED' => 'The cards could not be generated.',
    'BINGO_CARD_PRICE_CHANGED' => 'The card price changed (was :expected, now :current). Review the new amount before charging.',
    'BINGO_CARD_DUPLICATE_RECEIPT' => 'This receipt number was already used for another sale. Each payment can back only one sale.',
    'BINGO_CARD_PAYMENT_RECORD_FAILED' => 'The sale payment could not be recorded. No cards were generated; please try again.',
    'BINGO_CARD_UNAUTHORIZED_ROLE' => 'You do not have permission to buy cards with this role.',
    'BINGO_CARD_UNSUPPORTED_CURRENCY_ROLE' => 'Your role does not support operating with this currency.',
    'BINGO_CARD_UNSUPPORTED_COMMERCE_ROLE' => 'Your role does not support this commerce operation.',
    'BINGO_CARD_UNSUPPORTED_COMMISSION_ROLE' => 'Your role does not support the requested commission calculation.',
    'BINGO_CARD_INVALID_MANAGER_ROLE' => 'The manager\'s role is not valid for this operation.',
    'BINGO_CARD_INVALID_EXCHANGE_RATE' => 'The exchange rate is not valid.',
    'BINGO_CARD_UNAUTHORIZED' => 'You do not have permission to perform this action.',
    'BINGO_CARD_CUSTOM_NOT_ALLOWED' => 'This room does not allow custom cards.',
    'BINGO_CARD_CUSTOM_EXCEEDS_COUNT' => 'The number of custom cards exceeds the total requested count.',
    'BINGO_CARD_CUSTOM_EXCEED_EVERY_X_LIMIT' => 'With this number of cards you can only customize :max.',
    'BINGO_CARD_CUSTOM_MATRIX_INVALID' => 'Card #:position: :error',
    'BINGO_CARD_INSUFFICIENT_BALANCE' => 'Insufficient wallet balance. Current balance: :balance, Required amount: :required',
    'BINGO_CARD_NO_ACTIVE_WALLET' => 'You do not have an active wallet for this purchase.',

    // Wallet (generic, App\Domains\Wallet\Exceptions)
    'WALLET_UNAVAILABLE' => 'We could not reach the wallet right now. Please try again later.',
];
