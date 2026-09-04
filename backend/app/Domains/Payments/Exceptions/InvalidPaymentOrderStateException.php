<?php

declare(strict_types=1);

namespace App\Domains\Payments\Exceptions;

use RuntimeException;

class InvalidPaymentOrderStateException extends RuntimeException
{
}
