<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Services;

use App\Domains\Marketplace\Crypto\DTOs\RawCryptoTransaction;
use App\Domains\Marketplace\Crypto\Exceptions\InvalidCryptoTransactionException;
use App\Domains\Marketplace\Crypto\Models\CryptoDeposit;
use App\Domains\Marketplace\Crypto\Models\CryptoTransaction;
use App\Domains\Marketplace\Crypto\Models\DepositAddress;
use App\Models\Currency;
use App\Domains\Wallet\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Procesa una transacción cripto ya detectada por una fuente externa (nodo/
 * indexador de blockchain — fuera de alcance de esta fase, ver docblock de
 * clase abajo) y, si pasa todas las validaciones, acredita el depósito al
 * usuario correspondiente. Ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 22
 * para el diagrama de flujo exacto que este método implementa paso a paso.
 *
 * FASE 6 (esqueleto, sin custodia real): este servicio NO escucha ninguna
 * blockchain real — no existe todavía el proceso que detecta transacciones
 * entrantes (un listener de eventos del nodo, un poller contra un indexador
 * como TronGrid, etc.). process() recibe la transacción ya parseada como
 * RawCryptoTransaction, sea desde un comando de consola para pruebas
 * manuales, o desde ese listener real el día que exista. Toda la lógica de
 * validación (whitelist de red/token, confirmaciones mínimas, idempotencia)
 * SÍ es real y aplica sin importar de dónde venga la transacción.
 */
final class DepositWatcher
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {
    }

    /**
     * @throws InvalidCryptoTransactionException Si la transacción no pasa
     *   alguna validación — nunca se acredita nada en ese caso.
     */
    public function process(RawCryptoTransaction $raw): CryptoDeposit
    {
        $this->assertNetworkAllowed($raw->network);
        $this->assertTokenAllowed($raw->token, $raw->network);

        $depositAddress = DepositAddress::where('address', $raw->toAddress)
            ->where('network', $raw->network)
            ->active()
            ->first();

        if (!$depositAddress) {
            throw new InvalidCryptoTransactionException(
                "La dirección destino '{$raw->toAddress}' no está asignada a ningún usuario."
            );
        }

        $this->assertMinConfirmations($raw->confirmations);

        // Idempotencia real: tx_hash es UNIQUE en la BD. El SELECT de abajo
        // evita el trabajo de crear la wallet/movement en el camino común de
        // un reintento; el UNIQUE constraint es la defensa que realmente
        // impide doble-acreditación si dos llamadas concurrentes procesan el
        // mismo hash a la vez (ver el catch de QueryException más abajo).
        $existingTx = CryptoTransaction::where('tx_hash', $raw->txHash)->first();
        if ($existingTx) {
            $existingDeposit = CryptoDeposit::where('crypto_transaction_id', $existingTx->id)->first();
            if ($existingDeposit) {
                return $existingDeposit;
            }
        }

        $currency = Currency::where('iso_code', strtoupper($raw->token))->first();
        if (!$currency) {
            throw new InvalidCryptoTransactionException("Token '{$raw->token}' sin moneda configurada en el sistema.");
        }

        $wallet = $this->walletService->findActiveWallet((string) $depositAddress->user_id, (string) $currency->id);
        if (!$wallet) {
            throw new InvalidCryptoTransactionException(
                "El usuario {$depositAddress->user_id} no tiene una billetera activa en {$raw->token}."
            );
        }

        try {
            return DB::transaction(function () use ($raw, $depositAddress, $wallet) {
                $transaction = CryptoTransaction::create([
                    'tx_hash'       => $raw->txHash,
                    'network'       => $raw->network,
                    'token'         => strtoupper($raw->token),
                    'direction'     => CryptoTransaction::DIRECTION_DEPOSIT,
                    'from_address'  => $raw->fromAddress,
                    'to_address'    => $raw->toAddress,
                    'amount'        => $raw->amount,
                    'status'        => CryptoTransaction::STATUS_CONFIRMED,
                    'confirmations' => $raw->confirmations,
                    'user_id'       => $depositAddress->user_id,
                    'wallet_id'     => $wallet->id,
                    'confirmed_at'  => now(),
                ]);

                $deposit = CryptoDeposit::create([
                    'crypto_transaction_id' => $transaction->id,
                ]);

                $movement = $this->walletService->recordCryptoDeposit(
                    wallet: $wallet,
                    amount: $raw->amount,
                    description: "Depósito {$raw->token} — tx {$raw->txHash}",
                    idempotencyKey: "crypto_deposit:{$raw->txHash}",
                );

                $deposit->update(['wallet_movement_id' => $movement->id]);
                $transaction->update(['status' => CryptoTransaction::STATUS_CREDITED]);

                Log::info('[DepositWatcher] Depósito cripto acreditado', [
                    'tx_hash'    => $raw->txHash,
                    'user_id'    => $depositAddress->user_id,
                    'amount'     => $raw->amount,
                    'token'      => $raw->token,
                    'network'    => $raw->network,
                ]);

                return $deposit->fresh();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'tx_hash')) {
                $existingTx = CryptoTransaction::where('tx_hash', $raw->txHash)->first();
                if ($existingTx) {
                    $existingDeposit = CryptoDeposit::where('crypto_transaction_id', $existingTx->id)->first();
                    if ($existingDeposit) {
                        return $existingDeposit;
                    }
                }
            }
            throw $e;
        }
    }

    private function assertNetworkAllowed(string $network): void
    {
        $allowedNetworks = array_column(config('crypto.tokens', []), 'network');

        if (!in_array($network, $allowedNetworks, true)) {
            throw new InvalidCryptoTransactionException("Red '{$network}' no está en la whitelist configurada.");
        }
    }

    private function assertTokenAllowed(string $token, string $network): void
    {
        $tokens = config('crypto.tokens', []);
        $tokenConfig = $tokens[strtolower($token)] ?? null;

        if (!$tokenConfig || $tokenConfig['network'] !== $network) {
            throw new InvalidCryptoTransactionException(
                "Token '{$token}' no está permitido en la red '{$network}' según la configuración."
            );
        }
    }

    private function assertMinConfirmations(int $confirmations): void
    {
        $min = (int) config('crypto.min_confirmations', 19);

        if ($confirmations < $min) {
            throw new InvalidCryptoTransactionException(
                "Confirmaciones insuficientes: {$confirmations} de {$min} requeridas."
            );
        }
    }
}
