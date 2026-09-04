<?php

namespace App\Domains\Games\Bingo\Exceptions\Card;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio del flujo de generación/compra de cartones de Bingo
 * (BingoCardGenerateService).
 *
 * IMPORTANTE:
 * - El dominio expone ERROR CODES, no mensajes humanos. El texto legible se
 *   resuelve en Handler::resolveMessage() (trans('errors.{code}') con
 *   fallback al propio código), y el HTTP status lo declara cada factory
 *   method (ver DomainException::$httpStatus) — antes vivía solo en
 *   Handler::resolveStatusCode(), un mapa centralizado que se desincronizaba
 *   silenciosamente de las clases reales.
 */
final class BingoCardGenerateException extends DomainException
{
    public static function sellerNotFound(): self
    {
        return new self('BINGO_CARD_SELLER_NOT_FOUND', 404);
    }

    public static function commerceNotFound(): self
    {
        return new self('BINGO_CARD_COMMERCE_NOT_FOUND', 404);
    }

    /**
     * El Seller, su Subsidiary (si aplica) o su Commerce no están ACTIVE —
     * ver Seller::hasActiveHierarchy(). 409: no es "no encontrado" (404),
     * es un conflicto de estado de negocio válido pero no operable ahora.
     */
    public static function hierarchyNotActive(): self
    {
        return new self('BINGO_CARD_HIERARCHY_NOT_ACTIVE', 409);
    }

    public static function roomNotAvailable(): self
    {
        return new self('BINGO_CARD_ROOM_NOT_AVAILABLE', 404);
    }

    public static function currencyNotAvailable(): self
    {
        return new self('BINGO_CARD_CURRENCY_NOT_AVAILABLE', 422);
    }

    public static function cardGenerationFailed(): self
    {
        return new self('BINGO_CARD_GENERATION_FAILED', 422);
    }

    public static function unauthorizedRole(string $role): self
    {
        return new self('BINGO_CARD_UNAUTHORIZED_ROLE', 403);
    }

    public static function unsupportedCurrencyRole(string $role): self
    {
        return new self('BINGO_CARD_UNSUPPORTED_CURRENCY_ROLE', 422);
    }

    public static function unsupportedCommerceRole(string $role): self
    {
        return new self('BINGO_CARD_UNSUPPORTED_COMMERCE_ROLE', 422);
    }

    public static function unsupportedCommissionRole(string $role): self
    {
        return new self('BINGO_CARD_UNSUPPORTED_COMMISSION_ROLE', 422);
    }

    public static function invalidManagerRole(): self
    {
        return new self('BINGO_CARD_INVALID_MANAGER_ROLE', 422);
    }

    public static function invalidExchangeRate(): self
    {
        return new self('BINGO_CARD_INVALID_EXCHANGE_RATE', 422);
    }

    public static function unauthorized(): self
    {
        return new self('BINGO_CARD_UNAUTHORIZED', 403);
    }

    public static function customCardsNotAllowed(): self
    {
        return new self('BINGO_CARD_CUSTOM_NOT_ALLOWED', 403);
    }

    public static function customCardsExceedCount(): self
    {
        return new self('BINGO_CARD_CUSTOM_EXCEEDS_COUNT', 422);
    }

    public static function customCardsExceedEveryXLimit(int $maxAllowed): self
    {
        return new self('BINGO_CARD_CUSTOM_EXCEED_EVERY_X_LIMIT', 422, replacements: [
            'max' => $maxAllowed,
        ]);
    }

    public static function customCardMatrixInvalid(int $position, string $error): self
    {
        return new self('BINGO_CARD_CUSTOM_MATRIX_INVALID', 422, replacements: [
            'position' => $position,
            'error'    => $error,
        ]);
    }

    public static function noActiveWallet(): self
    {
        return new self('BINGO_CARD_NO_ACTIVE_WALLET', 422);
    }

    public static function insufficientBalance(float $currentBalance, float $requiredAmount): self
    {
        return new self('BINGO_CARD_INSUFFICIENT_BALANCE', 422, replacements: [
            'balance'  => $currentBalance,
            'required' => $requiredAmount,
        ]);
    }
}
