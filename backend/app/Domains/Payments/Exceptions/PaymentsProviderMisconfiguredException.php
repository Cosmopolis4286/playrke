<?php

declare(strict_types=1);

namespace App\Domains\Payments\Exceptions;

use RuntimeException;

/**
 * Lanzada al intentar usar TronGridProvider sin PAYMENTS_TRON_XPUB
 * configurado, o al intentar bindear MockBlockchainProvider en producción.
 * Nunca debe resolverse "en silencio" con una dirección o dato falso.
 *
 * A propósito NO es una DomainException (ni custom ni nativa): es un fallo
 * de configuración del servidor, no una regla de negocio del usuario. Como
 * RuntimeException llega a la rama 3 (Throwable genérico) de
 * bootstrap/app.php, que SÍ reporta vía Log::critical (dontReport() solo
 * excluye DomainException::class) y sanea el mensaje en producción — el
 * mensaje real (qué falta configurar) queda en el log, nunca expuesto al
 * cliente. Si esto fuera una DomainException caería en dontReport() y su
 * mensaje interno (rutas, detalle de configuración) se expondría directo
 * al cliente sin loguearse — exactamente al revés de lo que un error de
 * configuración de servidor necesita.
 */
class PaymentsProviderMisconfiguredException extends RuntimeException
{
}
