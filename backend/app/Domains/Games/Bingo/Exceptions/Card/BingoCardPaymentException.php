<?php

namespace App\Domains\Games\Bingo\Exceptions\Card;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de la revisión de pagos de cartones (BingoCardPaymentReviewService).
 * Mismo contrato que BingoCardGenerateException: código + HTTP status, el
 * texto vive en lang/{es,en}/errors.php.
 */
final class BingoCardPaymentException extends DomainException
{
    /**
     * El sorteo ya inició: aprobar ahora activa cartones que ya perdieron las
     * bolas cantadas. Se exige confirmación explícita (confirm_late).
     */
    public static function lateApprovalNeedsConfirmation() : self
    {
        return new self('BINGO_PAYMENT_LATE_APPROVAL_CONFIRM', 409);
    }
}
