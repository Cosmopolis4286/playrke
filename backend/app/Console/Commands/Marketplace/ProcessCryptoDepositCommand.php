<?php

namespace App\Console\Commands\Marketplace;

use App\Domains\Marketplace\Crypto\DTOs\RawCryptoTransaction;
use App\Domains\Marketplace\Crypto\Exceptions\InvalidCryptoTransactionException;
use App\Domains\Marketplace\Crypto\Services\DepositWatcher;
use Illuminate\Console\Command;

/**
 * Entry point manual para procesar un depósito cripto — Fase 6 (esqueleto).
 * Sin un listener real de blockchain todavía (ver docblock de
 * DepositWatcher), este comando es la única forma de invocar
 * DepositWatcher::process() hoy: pruebas manuales, o un operador
 * acreditando un depósito reportado externamente mientras no exista
 * automatización real.
 *
 * php artisan marketplace:process-crypto-deposit \
 *   --tx-hash=... --network=TRON --token=USDT \
 *   --from=... --to=... --amount=10.5 --confirmations=20
 */
class ProcessCryptoDepositCommand extends Command
{
    protected $description = 'Procesa un depósito cripto ya detectado (Fase 6, sin listener real de blockchain todavía)';
    protected $signature = 'marketplace:process-crypto-deposit
        {--tx-hash= : Hash de la transacción on-chain}
        {--network= : Red, ej. TRON}
        {--token= : Símbolo del token, ej. USDT}
        {--from= : Dirección origen}
        {--to= : Dirección destino (debe estar asignada a un usuario)}
        {--amount= : Monto}
        {--confirmations=0 : Confirmaciones actuales}';

    public function handle(DepositWatcher $watcher) : int
    {
        if (! config('crypto.deposits_enabled')) {
            $this->error('Los depósitos cripto no están habilitados (crypto.deposits_enabled=false).');

            return self::FAILURE;
        }

        $raw = new RawCryptoTransaction(
            txHash: (string) $this->option('tx-hash'),
            network: (string) $this->option('network'),
            token: (string) $this->option('token'),
            fromAddress: (string) $this->option('from'),
            toAddress: (string) $this->option('to'),
            amount: (float) $this->option('amount'),
            confirmations: (int) $this->option('confirmations'),
        );

        try {
            $deposit = $watcher->process($raw);
            $this->info("Depósito procesado: crypto_deposit_id={$deposit->id}, credited=" . ($deposit->isCredited() ? 'yes' : 'no'));

            return self::SUCCESS;
        } catch (InvalidCryptoTransactionException $e) {
            $this->error('Transacción rechazada: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
