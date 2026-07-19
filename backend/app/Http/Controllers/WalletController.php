<?php

namespace App\Http\Controllers;

use App\Http\Requests\WalletDepositRequest;
use App\Http\Requests\WalletWithdrawRequest;
use App\Models\MovementType;
use App\Services\Roles\RoleUserService;
use App\Services\WalletService;
use App\Services\WithdrawalLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService         $walletService,
        private readonly WithdrawalLimitService $limitService,
        private readonly RoleUserService        $roleUserService,
    ) {}

    /**
     * Página de billetera del jugador.
     */
    public function index(): Response
    {
        // roles?->first()?->prefix no es determinístico si el usuario tiene
        // más de un rol (sin orden garantizado en la relación) — se usa el
        // mismo resolutor de "rol activo" que el resto de la app (RoleUserService),
        // con fallback a null si no hay rol activo válido para no romper la página.
        try {
            $rolePrefix = $this->roleUserService->getActiveUserRolePrefix((int) Auth::id());
        } catch (\DomainException) {
            $rolePrefix = null;
        }

        return Inertia::render('Wallet/Index', [
            'wallets'       => $this->walletService->getPlayerWallets(),
            'movementTypes' => MovementType::active()
                ->visibleTo($rolePrefix)
                ->orderBy('sort_order')
                ->get(['code', 'label', 'description', 'icon', 'color', 'is_credit', 'game_group', 'sort_order']),
        ]);
    }

    /**
     * POST /wallet/deposit
     * El cliente envía currency_id; el backend resuelve la wallet del usuario autenticado.
     */
    public function deposit(WalletDepositRequest $request): JsonResponse
    {
        try {
            $wallet = $this->walletService->findOwnedWalletByCurrency(
                $request->input('currency_id')
            );

            $file = $request->file('receipt');

            if (! $file || ! $file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo de comprobante no se recibió correctamente. Intenta de nuevo.',
                ], 422);
            }

            $filename    = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $destination = storage_path('app/public/wallet/receipts');

            if (! is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $file->move($destination, $filename);

            $wallet = $this->walletService->requestDeposit(
                wallet:    $wallet,
                amount:    (float) $request->input('amount'),
                receipt:   'wallet/receipts/' . $filename,
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
        } catch (\Throwable $e) {
            Log::error('[WalletController@deposit] ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Error interno. Intenta de nuevo.'], 500);
        }
    }

    /**
     * POST /wallet/withdraw
     * El cliente envía currency_id; el backend resuelve la wallet del usuario autenticado.
     */
    public function withdraw(WalletWithdrawRequest $request): JsonResponse
    {
        try {
            $wallet = $this->walletService->findOwnedWalletByCurrency(
                $request->input('currency_id')
            );

            $wallet = $this->walletService->requestWithdraw(
                wallet:        $wallet,
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
        } catch (\Throwable $e) {
            Log::error('[WalletController@withdraw] ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Error interno. Intenta de nuevo.'], 500);
        }
    }

    /**
     * GET /wallet/movements?currency_id=xxx&page=1&per_page=20
     */
    public function movements(Request $request): JsonResponse
    {
        $currencyId = (string) $request->query('currency_id', '');
        $perPage    = min((int) $request->query('per_page', 20), 100);

        try {
            $wallet    = $this->walletService->findOwnedWalletByCurrency($currencyId);
            $paginator = $this->walletService->getMovements($wallet, $perPage);

            $items = collect($paginator->items())->map(fn(\App\Models\WalletMovement $m) => [
                'id'             => (string) $m->id,
                'wallet_id'      => (string) $m->wallet_id,
                'user_id'        => (string) $m->user_id,
                'type'           => $m->type,
                'status'         => $m->status,
                'amount'         => (float) $m->amount,
                'balance_before' => (float) $m->balance_before,
                'balance_after'  => (float) $m->balance_after,
                'description'    => $m->description,
                'reference_id'   => $m->reference_id,
                'receipt_path'   => $m->receipt_path,
                'receipt_url'    => $m->receipt_path ? route('wallet.receipt', ['filename' => basename($m->receipt_path)]) : null,
                'admin_notes'    => $m->admin_notes,
                'prize_type'     => $m->prize_type,
                'game_id'        => $m->game_id,
                'processed_at'   => $m->processed_at?->toISOString(),
                'created_at'     => $m->created_at,
                'updated_at'     => $m->updated_at,
            ]);

            return response()->json([
                'success' => true,
                'data'    => $items,
                'meta'    => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('[WalletController@movements] ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Error interno.'], 500);
        }
    }

    /**
     * GET /wallet/movements/{id}
     * Devuelve un movimiento específico del usuario autenticado.
     */
    public function showMovement(string $id): JsonResponse
    {
        try {
            $m = \App\Models\WalletMovement::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'             => (string) $m->id,
                    'wallet_id'      => (string) $m->wallet_id,
                    'user_id'        => (string) $m->user_id,
                    'type'           => $m->type,
                    'status'         => $m->status,
                    'amount'         => (float) $m->amount,
                    'balance_before' => (float) $m->balance_before,
                    'balance_after'  => (float) $m->balance_after,
                    'description'    => $m->description,
                    'reference_id'   => $m->reference_id,
                    'receipt_path'   => $m->receipt_path,
                    'receipt_url'    => $m->receipt_path ? route('wallet.receipt', ['filename' => basename($m->receipt_path)]) : null,
                    'admin_notes'    => $m->admin_notes,
                    'prize_type'     => $m->prize_type,
                    'game_id'        => $m->game_id,
                    'processed_at'   => $m->processed_at?->toISOString(),
                    'created_at'     => $m->created_at,
                    'updated_at'     => $m->updated_at,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Movimiento no encontrado.'], 404);
        } catch (\Throwable $e) {
            Log::error('[WalletController@showMovement] ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno.'], 500);
        }
    }

    /**
     * GET /wallet/directories?currency_id=xxx
     */
    public function userDirectories(Request $request): JsonResponse
    {
        $currencyId = (string) $request->query('currency_id', '');

        try {
            $wallet      = $this->walletService->findOwnedWalletByCurrency($currencyId);
            $directories = $this->walletService->getUserDirectories($wallet);

            return response()->json(['success' => true, 'data' => $directories]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('[WalletController@userDirectories] ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => true, 'data' => []]);
        }
    }

    /**
     * GET /wallet/pending-operation?currency_id=xxx&type=DEPOSIT|WITHDRAWAL
     */
    public function pendingOperation(Request $request): JsonResponse
    {
        $currencyId = (string) $request->query('currency_id', '');
        $type       = strtoupper((string) $request->query('type', ''));

        if (! in_array($type, ['DEPOSIT', 'WITHDRAWAL'], true)) {
            return response()->json(['success' => true, 'pending' => null]);
        }

        try {
            $wallet  = $this->walletService->findOwnedWalletByCurrency($currencyId);
            $pending = $this->walletService->getPendingOperation((string) $wallet->id, $type);

            return response()->json([
                'success' => true,
                'pending' => $pending ? [
                    'id'          => (string) $pending->id,
                    'type'        => $pending->type,
                    'status'      => $pending->status,
                    'amount'      => (float) $pending->amount,
                    'description' => $pending->description,
                    'created_at'  => $pending->created_at,
                ] : null,
            ]);
        } catch (\Throwable) {
            return response()->json(['success' => true, 'pending' => null]);
        }
    }

    /**
     * GET /wallet/limits?currency_id=xxx
     * Devuelve los límites de retiro y el uso actual del período.
     */
    public function limits(Request $request): JsonResponse
    {
        $currencyId = (string) $request->query('currency_id', '');

        try {
            $wallet   = $this->walletService->findOwnedWalletByCurrency($currencyId);
            $kycLevel = (int) (Auth::user()?->kyc_level ?? 0);
            $usage    = $this->limitService->usage($wallet, $kycLevel);

            return response()->json(['success' => true, 'data' => $usage]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('[WalletController@limits] ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno.'], 500);
        }
    }
}
