<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Games\Shared\Transactions\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    /**
     * Get user wallets
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWallets(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallets = $user->wallets()->with('currency')->get();

        return response()->json([
            'success' => true,
            'wallets' => $wallets->map(function ($wallet) {
                return [
                    'id' => $wallet->id,
                    'currency' => $wallet->currency->code ?? 'USD',
                    'currency_symbol' => $wallet->currency->symbol ?? '$',
                    'balance' => $wallet->balance,
                    'formatted_balance' => number_format($wallet->balance, 2),
                ];
            }),
        ]);
    }

    /**
     * Get wallet balance
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getBalance(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get balance for specific currency or all
        $currencyCode = $request->query('currency');

        if ($currencyCode) {
            $wallet = $user->wallets()
                ->whereHas('currency', function ($q) use ($currencyCode) {
                    $q->where('code', $currencyCode);
                })
                ->with('currency')
                ->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Billetera no encontrada',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'currency' => $currencyCode,
                'balance' => $wallet->balance,
                'formatted_balance' => number_format($wallet->balance, 2),
            ]);
        }

        // Return all balances
        $wallets = $user->wallets()->with('currency')->get();

        return response()->json([
            'success' => true,
            'balances' => $wallets->mapWithKeys(function ($wallet) {
                return [
                    $wallet->currency->code => [
                        'balance' => $wallet->balance,
                        'formatted' => number_format($wallet->balance, 2),
                    ],
                ];
            }),
        ]);
    }

    /**
     * Deposit funds
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'string'],
            'reference' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        $wallet = $user->wallets()
            ->whereHas('currency', function ($q) use ($validated) {
                $q->where('code', $validated['currency']);
            })
            ->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Billetera no encontrada',
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'deposit',
                'amount' => $validated['amount'],
                'status' => 'pending',
                'method' => $validated['method'],
                'reference' => $validated['reference'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de depósito creada',
                'transaction' => [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear solicitud de depósito',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Withdraw funds
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function withdraw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'string'],
            'account_info' => ['required', 'array'],
        ]);

        $user = $request->user();

        $wallet = $user->wallets()
            ->whereHas('currency', function ($q) use ($validated) {
                $q->where('code', $validated['currency']);
            })
            ->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Billetera no encontrada',
            ], 404);
        }

        // Check balance
        if ($wallet->balance < $validated['amount']) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo insuficiente',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'withdrawal',
                'amount' => $validated['amount'],
                'status' => 'pending',
                'method' => $validated['method'],
                'account_info' => json_encode($validated['account_info']),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de retiro creada',
                'transaction' => [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear solicitud de retiro',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get transaction history
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Transaction::where('user_id', $user->id)
            ->with(['wallet.currency'])
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by currency
        if ($request->has('currency')) {
            $query->whereHas('wallet.currency', function ($q) use ($request) {
                $q->where('code', $request->currency);
            });
        }

        // Filter by date range
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        $transactions = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->wallet->currency->code ?? 'USD',
                    'status' => $transaction->status,
                    'method' => $transaction->method,
                    'reference' => $transaction->reference,
                    'created_at' => $transaction->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Get single transaction
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function getTransaction(int $id, Request $request): JsonResponse
    {
        $user = $request->user();

        $transaction = Transaction::where('user_id', $user->id)
            ->with(['wallet.currency'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'transaction' => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'currency' => $transaction->wallet->currency->code ?? 'USD',
                'status' => $transaction->status,
                'method' => $transaction->method,
                'reference' => $transaction->reference,
                'account_info' => json_decode($transaction->account_info ?? '{}', true),
                'created_at' => $transaction->created_at,
                'updated_at' => $transaction->updated_at,
            ],
        ]);
    }
}
