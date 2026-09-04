<?php

declare(strict_types=1);

namespace App\Domains\Payments\Exceptions;

use RuntimeException;

/**
 * Lanzada al intentar usar TronGridProvider sin PAYMENTS_TRON_XPUB
 * configurado, o al intentar bindear MockBlockchainProvider en producción.
 * Nunca debe resolverse "en silencio" con una dirección o dato falso.
 */
class PaymentsProviderMisconfiguredException extends RuntimeException
{
}
