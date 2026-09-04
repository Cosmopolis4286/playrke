<?php

declare(strict_types=1);

namespace App\Domains\Payments\Contracts;

use App\Domains\Payments\DTOs\InboundTransaction;

/**
 * Puerto único del que depende toda la lógica de negocio de Payments
 * (PaymentOrderService, BlockchainWatcherService). Ver
 * PAYMENTS_USDT_TRC20_DESIGN.md sección 2.
 *
 * Deliberadamente de SOLO LECTURA: derivar direcciones públicas desde un
 * XPUB (BIP32 sin private key) y consultar transacciones/confirmaciones ya
 * ocurridas en la red. No expone ningún método de firma/broadcast — este
 * módulo nunca envía fondos, solo detecta pagos entrantes. Si en el futuro
 * se necesita firmar (retiros), esa responsabilidad vive en un puerto
 * separado (ver CryptoGateway::broadcastWithdrawal, dominio Marketplace\Crypto,
 * hoy bloqueado hasta resolver proveedor de custodia).
 *
 * Cambiar de proveedor (TronGrid -> nodo propio -> otro indexador -> otra
 * red) es un solo binding nuevo en PaymentServiceProvider::register() —
 * ningún consumidor de esta interfaz conoce el nombre del proveedor
 * concreto.
 */
interface BlockchainProviderInterface
{
    /**
     * Identificador de red que expone este proveedor (ej. "TRON").
     */
    public function network(): string;

    /**
     * Deriva la dirección pública en el índice indicado, a partir del XPUB
     * configurado para este proveedor. Nunca requiere ni tiene acceso a la
     * clave privada/seed.
     */
    public function deriveAddress(int $derivationIndex): string;

    /**
     * Transacciones entrantes del token configurado (ej. USDT-TRC20)
     * recibidas por $address desde el bloque $sinceBlock (o desde el
     * principio si es null). Nunca incluye transacciones salientes.
     *
     * @return InboundTransaction[]
     */
    public function getIncomingTransactions(string $address, ?int $sinceBlock = null): array;

    /**
     * Confirmaciones actuales de una transacción ya observada por su hash.
     * Devuelve null si la transacción no existe (nunca lanzada como error:
     * "no encontrada todavía" es un resultado válido durante el polling).
     */
    public function getConfirmations(string $txHash): ?int;

    /**
     * Contrato del token que este proveedor está observando (ej. dirección
     * del contrato oficial USDT-TRC20) — permite validar que una transacción
     * recibida corresponde exactamente al token esperado, nunca inferido por
     * símbolo/nombre.
     */
    public function tokenContract(): string;
}
