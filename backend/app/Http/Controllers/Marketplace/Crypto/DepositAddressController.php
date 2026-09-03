<?php

namespace App\Http\Controllers\Marketplace\Crypto;

use App\Domains\Marketplace\Crypto\Services\DepositAddressService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * GET /wallet/deposit-address?currency=USDT
 * Ver MARKETPLACE_ARCHITECTURE_AUDIT.md secciones 10, 21 y 28.
 *
 * FASE 5 (esqueleto): la dirección devuelta viene de TronUsdtProvider, un
 * STUB — nunca es una dirección real capaz de recibir fondos. Este endpoint
 * queda detrás de config('crypto.deposits_enabled') (default false) para
 * que no aparezca en la UI de producción hasta que exista custodia real.
 */
class DepositAddressController extends Controller
{
    public function __construct(
        private readonly DepositAddressService $depositAddressService,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        if (!config('crypto.deposits_enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'Los depósitos cripto no están habilitados todavía.',
            ], 403);
        }

        $currencyCode = $request->query('currency', 'USDT');

        try {
            $address = $this->depositAddressService->getOrCreateByCurrencyCode((string) Auth::id(), $currencyCode);

            return response()->json([
                'success' => true,
                'data' => [
                    'address'  => $address->address,
                    'network'  => $address->network,
                    'currency' => $currencyCode,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => "Moneda '{$currencyCode}' no encontrada."], 404);
        } catch (\Throwable $e) {
            Log::error('[DepositAddressController@show] ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Error interno al resolver la dirección de depósito.'], 500);
        }
    }
}
