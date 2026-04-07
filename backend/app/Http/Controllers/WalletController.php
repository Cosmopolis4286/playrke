<?php

namespace App\Http\Controllers;

use App\Http\Requests\WalletDepositRequest;
use App\Http\Requests\WalletWithdrawRequest;
use App\Models\Directories\Crypto\Binance\BinanceAccounts;
use App\Models\Directories\Fiat\Brazil\PixKey;
use App\Models\Directories\Fiat\Venezuela\PagoMovil;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    /**
     * Página de billetera del jugador.
     */
    public function index(): Response
    {
        return Inertia::render('Wallet/Index', [
            'wallets' => $this->walletService->getPlayerWallets(),
        ]);
    }

    /**
     * Directorios del usuario agrupados por tipo, filtrados por moneda de la billetera.
     * GET /wallet/{walletId}/directories
     */
    public function userDirectories(string $walletId): JsonResponse
    {
        try {
            $userId = (string) Auth::id();
            $directories = $this->walletService->getUserDirectories($userId, $walletId);

            return response()->json(['success' => true, 'data' => $directories]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Solicitar depósito con comprobante (queda en PENDING hasta que admin confirme).
     */
    public function deposit(WalletDepositRequest $request): JsonResponse
    {
        try {
            $receiptPath = $request->file('receipt')->store('wallet/receipts', 'public');

            $wallet = $this->walletService->requestDeposit(
                walletId:  $request->input('wallet_id'),
                amount:    (float) $request->input('amount'),
                receipt:   $receiptPath,
                reference: $request->input('reference'),
                notes:     $request->input('notes'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de depósito enviada. Un administrador la revisará pronto.',
                'balance' => (float) $wallet->balance,
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Solicitar retiro indicando el directorio destino (queda en PENDING hasta que admin confirme).
     */
    public function withdraw(WalletWithdrawRequest $request): JsonResponse
    {
        try {
            $wallet = $this->walletService->requestWithdraw(
                walletId:      $request->input('wallet_id'),
                amount:        (float) $request->input('amount'),
                directoryId:   $request->input('directory_id'),
                directoryType: $request->input('directory_type'),
                notes:         $request->input('notes'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de retiro enviada. Un administrador procesará la transferencia.',
                'balance' => (float) $wallet->balance,
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Movimientos paginados de una billetera.
     * GET /wallet/{walletId}/movements?page=1&per_page=20
     */
    public function movements(Request $request, string $walletId): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);

        try {
            $paginator = $this->walletService->getMovements($walletId, $perPage);

            return response()->json([
                'success' => true,
                'data'    => $paginator->items(),
                'meta'    => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
