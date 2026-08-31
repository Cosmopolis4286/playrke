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
 *   fallback al propio código), y el HTTP status en Handler::resolveStatusCode().
 */
final class BingoCardGenerateException extends DomainException
{
    public static function sellerNotFound(): self
    {
        return new self('BINGO_CARD_SELLER_NOT_FOUND');
    }

    public static function commerceNotFound(): self
    {
        return new self('BINGO_CARD_COMMERCE_NOT_FOUND');
    }

    public static function roomNotAvailable(): self
    {
        return new self('BINGO_CARD_ROOM_NOT_AVAILABLE');
    }

    public static function currencyNotAvailable(): self
    {
        return new self('BINGO_CARD_CURRENCY_NOT_AVAILABLE');
    }

    public static function cardGenerationFailed(): self
    {
        return new self('BINGO_CARD_GENERATION_FAILED');
    }

    public static function unauthorizedRole(string $role): self
    {
        return new self('BINGO_CARD_UNAUTHORIZED_ROLE');
    }

    public static function unsupportedCurrencyRole(string $role): self
    {
        return new self('BINGO_CARD_UNSUPPORTED_CURRENCY_ROLE');
    }

    public static function unsupportedCommerceRole(string $role): self
    {
        return new self('BINGO_CARD_UNSUPPORTED_COMMERCE_ROLE');
    }

    public static function unsupportedCommissionRole(string $role): self
    {
        return new self('BINGO_CARD_UNSUPPORTED_COMMISSION_ROLE');
    }

    public static function invalidManagerRole(): self
    {
        return new self('BINGO_CARD_INVALID_MANAGER_ROLE');
    }

    public static function invalidExchangeRate(): self
    {
        return new self('BINGO_CARD_INVALID_EXCHANGE_RATE');
    }

    public static function unauthorized(): self
    {
        return new self('BINGO_CARD_UNAUTHORIZED');
    }

    public static function customCardsNotAllowed(): self
    {
        return new self('BINGO_CARD_CUSTOM_NOT_ALLOWED');
    }

    public static function customCardsExceedCount(): self
    {
        return new self('BINGO_CARD_CUSTOM_EXCEEDS_COUNT');
    }

    public static function customCardsExceedEveryXLimit(int $maxAllowed): self
    {
        return new self("Con esta cantidad de cartones solo puedes personalizar {$maxAllowed}.");
    }

    public static function customCardMatrixInvalid(int $position, string $error): self
    {
        return new self("Cartón #{$position}: {$error}");
    }

    public static function noActiveWallet(): self
    {
        return new self('BINGO_CARD_NO_ACTIVE_WALLET');
    }

    public static function insufficientBalance(float $currentBalance, float $requiredAmount): self
    {
        return new self(
            "Saldo insuficiente en la billetera. Saldo actual: {$currentBalance}, Monto requerido: {$requiredAmount}"
        );
    }
}
