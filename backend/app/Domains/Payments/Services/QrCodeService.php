<?php

declare(strict_types=1);

namespace App\Domains\Payments\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Genera el QR de una dirección de depósito TRON. Ver
 * PAYMENTS_USDT_TRC20_DESIGN.md sección 5 (investigación previa a esta
 * implementación): no existe ningún esquema de URI "tron:" estándar
 * soportado al escanear por las wallets mayoritarias (TronLink, Trust
 * Wallet, imToken, Klever no comparten ninguno) — el propio deep link de
 * TronLink es propietario y solo funciona invocado desde un botón de dApp,
 * nunca al escanear un QR arbitrario.
 *
 * La práctica real de la industria (confirmada en Binance/OKX) es codificar
 * ÚNICAMENTE la dirección en texto plano, sin monto ni contrato — el monto
 * exacto se muestra como texto junto al QR para que el usuario lo tipee
 * manualmente en su wallet. Nunca inventar un formato propio no soportado.
 */
final class QrCodeService
{
    public function pngForAddress(string $address): string
    {
        $result = (new Builder())->build(
            writer: new PngWriter(),
            data: $address,
            size: 320,
            margin: 10,
        );

        return $result->getString();
    }
}
