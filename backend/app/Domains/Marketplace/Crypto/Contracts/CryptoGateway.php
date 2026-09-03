<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Contracts;

/**
 * Límite de responsabilidad entre la aplicación y la capa de custodia real.
 * Ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 21.
 *
 * Laravel NUNCA posee la master seed/private key. Una implementación real
 * de esta interfaz llama a un Signer/Custody Layer externo (HSM/KMS/proveedor
 * custodial) vía HTTP/RPC — nunca deriva direcciones ni firma en el mismo
 * proceso que sirve tráfico HTTP de la aplicación.
 *
 * TronUsdtProvider (implementación actual) es un STUB: deriveAddress()
 * genera una dirección determinística falsa sin ningún proveedor real
 * detrás, y broadcastWithdrawal()/getConfirmations() lanzan
 * CryptoCustodyNotConfiguredException — ver ese archivo para el detalle.
 * Esto permite probar el flujo completo (asignar dirección a un usuario,
 * ver el shape de datos) sin mover fondos reales ni depender de un
 * proveedor externo hasta que la Fase 6-7 (custodia real) se desbloquee.
 */
interface CryptoGateway
{
    /**
     * Deriva (o pide al Signer que derive) la siguiente dirección de
     * depósito para un usuario. Devuelve el índice de derivación usado y la
     * dirección pública — nunca ninguna clave.
     *
     * @return array{derivation_index: int, address: string}
     */
    public function deriveAddress(string $userId): array;

    /**
     * Solicita al Signer que firme y transmita un retiro ya aprobado.
     * Devuelve el tx_hash de la transacción transmitida.
     */
    public function broadcastWithdrawal(string $toAddress, float $amount): string;

    /**
     * Confirmaciones actuales de una transacción ya transmitida.
     */
    public function getConfirmations(string $txHash): int;

    /**
     * Identificador de red que expone este gateway (ej. "TRON").
     */
    public function network(): string;
}
