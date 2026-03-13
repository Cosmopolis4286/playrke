<?php

namespace App\Http\Controllers;

use App\Http\Requests\WalletOperationRequest;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * Depositar en una billetera.
     */
    public function deposit(WalletOperationRequest $request): JsonResponse
    {
        try {
            $wallet = $this->walletService->deposit(
                $request->input('wallet_id'),
                (float) $request->input('amount'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Depósito registrado correctamente.',
                'balance' => (float) $wallet->balance,
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Retirar de una billetera.
     */
    public function withdraw(WalletOperationRequest $request): JsonResponse
    {
        try {
            $wallet = $this->walletService->withdraw(
                $request->input('wallet_id'),
                (float) $request->input('amount'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Retiro registrado correctamente.',
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
