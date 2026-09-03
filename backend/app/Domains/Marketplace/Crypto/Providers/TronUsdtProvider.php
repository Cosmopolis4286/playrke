<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Providers;

use App\Domains\Marketplace\Crypto\Contracts\CryptoGateway;
use App\Domains\Marketplace\Crypto\Exceptions\CryptoCustodyNotConfiguredException;

/**
 * STUB — ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 12. NO usar en
 * producción: deriveAddress() genera una dirección determinística falsa
 * (nunca capaz de recibir fondos reales), y las operaciones que requieren
 * custodia real (firmar/transmitir, consultar confirmaciones on-chain)
 * lanzan CryptoCustodyNotConfiguredException explícitamente en vez de
 * fingir un resultado.
 *
 * Esto existe para permitir probar el flujo completo de asignación de
 * dirección de depósito (DepositAddressService, endpoints, UI) sin mover
 * fondos reales ni depender de un proveedor externo, mientras la decisión
 * de custodia (HSM/KMS propio vs. proveedor custodial) y la validación
 * regulatoria siguen pendientes. Reemplazar esta clase completa por una
 * implementación real de CryptoGateway que hable con un Signer/Custody
 * Layer externo es el único cambio necesario para desbloquear las Fases 6-7
 * — ningún otro código de la aplicación debe cambiar.
 */
final class TronUsdtProvider implements CryptoGateway
{
    public function network(): string
    {
        return 'TRON';
    }

    /**
     * Dirección determinística FALSA derivada de derivation_index — jamás
     * corresponde a una wallet real en TRON. El formato ("T" + 33
     * caracteres) imita la forma de una dirección TRON real solo para que
     * el resto del sistema (validación de longitud, UI) se comporte como
     * lo haría con una dirección real, no para que sea usable.
     */
    public function deriveAddress(string $userId): array
    {
        $derivationIndex = $this->nextDerivationIndex();

        $hash = substr(hash('sha256', "stub-tron:{$userId}:{$derivationIndex}"), 0, 33);

        return [
            'derivation_index' => $derivationIndex,
            'address'          => 'T' . strtoupper($hash),
        ];
    }

    public function broadcastWithdrawal(string $toAddress, float $amount): string
    {
        throw new CryptoCustodyNotConfiguredException(
            'No hay proveedor de custodia real configurado — TronUsdtProvider es un stub (Fase 5). '
            . 'No se puede transmitir ningún retiro real todavía.'
        );
    }

    public function getConfirmations(string $txHash): int
    {
        throw new CryptoCustodyNotConfiguredException(
            'No hay proveedor de custodia real configurado — TronUsdtProvider es un stub (Fase 5). '
            . 'No se pueden consultar confirmaciones on-chain reales todavía.'
        );
    }

    /**
     * Índice de derivación secuencial global — en un provider real este
     * contador lo mantiene el Signer/Custody Layer, nunca la aplicación
     * (evitar colisiones entre réplicas de la app). Aquí se calcula desde
     * el máximo ya persistido en crypto_deposit_addresses, suficiente para
     * el stub de esta fase.
     */
    private function nextDerivationIndex(): int
    {
        $max = \App\Domains\Marketplace\Crypto\Models\DepositAddress::max('derivation_index');

        return ((int) $max) + 1;
    }
}
