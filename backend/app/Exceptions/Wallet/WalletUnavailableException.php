<?php
declare(strict_types=1);

namespace App\Exceptions\Wallet;

/**
 * Se lanza cuando la wallet de un usuario no puede ser localizada o está inactiva.
 * Typed exception para que los callers puedan distinguirla de otros RuntimeExceptions
 * sin depender de coincidencias de string en el mensaje.
 */
final class WalletUnavailableException extends \RuntimeException
{
    public static function notFound(string $userId, string $currencyId): self
    {
        return new self("Wallet no encontrada o inactiva para user={$userId} currency={$currencyId}");
    }
}
