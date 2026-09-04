<?php

declare(strict_types=1);

namespace App\Domains\Payments\Providers;

use App\Domains\Payments\Contracts\BlockchainProviderInterface;
use App\Domains\Payments\DTOs\InboundTransaction;
use App\Domains\Payments\Exceptions\PaymentsProviderMisconfiguredException;
use App\Domains\Payments\Services\Bip32PublicDerivationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Implementación real de BlockchainProviderInterface contra TRON mainnet.
 * Ver PAYMENTS_USDT_TRC20_DESIGN.md secciones 2 y 9.
 *
 * Deliberadamente sin librería "todo-en-uno" de TRON (ver evaluación previa:
 * ninguna candidata en PHP tenía madurez/adopción suficiente, y una
 * -iexbase/tron-api- mostró una bandera roja de supply-chain por un rewrite
 * completo reciente sin historial). En su lugar: HTTP directo contra la API
 * REST pública de TronGrid (lectura únicamente) + derivación BIP32 pública
 * aislada en Bip32PublicDerivationService.
 *
 * Solo lectura por diseño: nunca firma ni transmite nada. El XPUB
 * (config('payments.tron.xpub')) permite derivar direcciones públicas y
 * jamás permite gastar fondos, pero igual se trata como secreto de
 * configuración (nunca se loguea su valor).
 */
final class TronGridProvider implements BlockchainProviderInterface
{
    public function __construct(
        private readonly Bip32PublicDerivationService $derivation,
    ) {
    }

    public function network(): string
    {
        return 'TRON';
    }

    public function tokenContract(): string
    {
        return config('payments.tron.usdt_contract');
    }

    public function deriveAddress(int $derivationIndex): string
    {
        $xpub = config('payments.tron.xpub');

        // Diferido al punto de uso real (nunca al constructor): el
        // container resuelve este singleton en momentos donde no hay
        // ninguna orden que derivar (ej. `artisan route:list` instancia
        // controllers vía reflexión para inspeccionar type-hints) — fallar
        // ahí rompería comandos/CI que nunca tocan Payments, sin que exista
        // todavía ninguna operación real que justifique el error.
        if (empty($xpub)) {
            throw new PaymentsProviderMisconfiguredException(
                'PAYMENTS_TRON_XPUB no configurado — TronGridProvider no puede derivar direcciones reales.'
            );
        }

        return $this->derivation->deriveAddress($xpub, $derivationIndex);
    }

    /**
     * @return InboundTransaction[]
     */
    public function getIncomingTransactions(string $address, ?int $sinceBlock = null): array
    {
        $response = $this->client()
            ->get("/v1/accounts/{$address}/transactions/trc20", array_filter([
                'contract_address' => $this->tokenContract(),
                'only_to' => 'true',
                'limit' => 200,
                'order_by' => 'block_timestamp,asc',
                'min_block_timestamp' => $sinceBlock,
            ]));

        if ($response->failed()) {
            Log::channel('slack')->error('payments.tron_grid.request_failed', [
                'endpoint' => 'transactions/trc20',
                'address' => $address,
                'status' => $response->status(),
            ]);

            return [];
        }

        $transactions = [];

        foreach ($response->json('data', []) as $entry) {
            if (($entry['to'] ?? null) !== $address) {
                continue;
            }

            if (($entry['token_info']['address'] ?? null) !== $this->tokenContract()) {
                continue;
            }

            $decimals = (int) ($entry['token_info']['decimals'] ?? 6);
            $rawValue = $entry['value'] ?? '0';
            $amount = ((float) $rawValue) / (10 ** $decimals);

            $txHash = $entry['transaction_id'] ?? null;
            if (!$txHash) {
                continue;
            }

            $timestampMs = (int) ($entry['block_timestamp'] ?? 0);

            $transactions[] = new InboundTransaction(
                txHash: $txHash,
                fromAddress: $entry['from'] ?? '',
                toAddress: $entry['to'],
                tokenContract: $entry['token_info']['address'],
                amount: $amount,
                blockNumber: 0, // TronGrid no incluye block_number en este endpoint — se resuelve con getConfirmations()
                timestamp: intdiv($timestampMs, 1000),
                confirmations: 0,
            );
        }

        return $transactions;
    }

    public function getConfirmations(string $txHash): ?int
    {
        $infoResponse = $this->client()->post('/wallet/gettransactioninfobyid', [
            'value' => $txHash,
        ]);

        if ($infoResponse->failed() || empty($infoResponse->json('blockNumber'))) {
            return null;
        }

        $txBlock = (int) $infoResponse->json('blockNumber');

        $nowBlockResponse = $this->client()->post('/wallet/getnowblock');

        if ($nowBlockResponse->failed()) {
            return null;
        }

        $currentBlock = (int) $nowBlockResponse->json('block_header.raw_data.number');

        if ($currentBlock <= 0 || $txBlock <= 0) {
            return null;
        }

        return max(0, $currentBlock - $txBlock + 1);
    }

    private function client()
    {
        $request = Http::baseUrl(config('payments.tron.api_url'))
            ->acceptJson()
            ->timeout(15);

        $apiKey = config('payments.tron.api_key');
        if (!empty($apiKey)) {
            $request = $request->withHeaders(['TRON-PRO-API-KEY' => $apiKey]);
        }

        return $request;
    }
}
