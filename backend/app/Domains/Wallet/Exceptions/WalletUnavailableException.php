<?php
declare(strict_types=1);

namespace App\Domains\Wallet\Exceptions;

use App\Exceptions\Domain\DomainException;

/**
 * Se lanza cuando la wallet de un usuario no puede ser localizada o está inactiva.
 *
 * Ambos consumidores actuales (BingoCardLotRefundJob, RetryFailedBingoRefundsCommand)
 * la capturan internamente como fallo transitorio de infraestructura — nunca llega a
 * un controlador HTTP — pero como DomainException ya no expone user_id/currency_id en
 * texto plano si algún día sí escapara al pipeline HTTP (antes: mensaje crudo con IDs
 * internos servido directo al usuario).
 */
final class WalletUnavailableException extends DomainException
{
    /**
     * $userId/$currencyId no viajan en el mensaje (error code, no texto libre) —
     * el caller ya los registra en su propio log/registro de fallo antes de lanzar.
     * Se mantienen en la firma para que el sitio de lanzamiento documente qué wallet
     * faltó, aunque esta excepción no la exponga.
     */
    public static function notFound(string $userId, string $currencyId): self
    {
        return new self('WALLET_UNAVAILABLE', 503);
    }
}
