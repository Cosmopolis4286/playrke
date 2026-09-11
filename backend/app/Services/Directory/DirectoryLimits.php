<?php

declare(strict_types=1);

namespace App\Services\Directory;

/**
 * Límites de negocio compartidos por PagoMovil (VE) y PixKey (BR) — sin
 * esto, cualquier usuario podía acumular cuentas bancarias/PIX sin tope,
 * lo que además de ruido operativo es una señal de fraude común (rotar
 * cuentas destino para dificultar el rastreo de un retiro).
 *
 * El límite es por país/tipo de directorio (no agregado entre VE+BR): un
 * usuario legítimo puede razonablemente necesitar varias cuentas en su
 * propio país (banco personal + de familiar autorizado, etc.) pero no
 * decenas.
 */
final class DirectoryLimits
{
    public const MAX_RECORDS_PER_USER = 5;
}
