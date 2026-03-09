<?php

namespace App\Http\Controllers;

use App\Http\Requests\WalletOperationRequest;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
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
}
