<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Models\WalletMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WalletMovementController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(WalletMovement::class, 'movement');
    }

    public function index(): Response
    {
        return Inertia::render('Wallet/Movements/Index', [
            'title' => 'Movimientos de Billetera',
        ]);
    }

    /**
     * Lista paginada de movimientos con relaciones user y wallet.currency
     * GET /wallet-movements/fetch?page=1&per_page=20&type=&status=&search=
     */
    public function fetchList(Request $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {
            $perPage = (int) $request->query('per_page', 20);
            $type    = $request->query('type');
            $status  = $request->query('status');
            $search  = $request->query('search');

            $query = WalletMovement::with([
                'wallet:id,currency_id',
                'wallet.currency:id,iso_code,symbol,decimal_places',
                'user:id,first_name,last_name,email,username',
            ])
            ->orderByDesc('created_at');

            if ($type)   $query->where('type', strtoupper($type));
            if ($status) $query->where('status', strtoupper($status));
            if ($search) {
                $query->whereHas('user', fn($q) =>
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                );
            }

            $paginator = $query->paginate($perPage);

            return [
                'data' => collect($paginator->items())->map(fn(WalletMovement $m) => [
                    'id'             => (string) $m->id,
                    'type'           => $m->type,
                    'status'         => $m->status,
                    'amount'         => (float) $m->amount,
                    'balance_before' => (float) $m->balance_before,
                    'balance_after'  => (float) $m->balance_after,
                    'description'    => $m->description,
                    'reference_id'   => $m->reference_id,
                    'created_at'     => $m->created_at,
                    'currency'       => $m->wallet?->currency ? [
                        'iso_code'       => $m->wallet->currency->iso_code,
                        'symbol'         => $m->wallet->currency->symbol,
                        'decimal_places' => $m->wallet->currency->decimal_places,
                    ] : null,
                    'user' => $m->user ? [
                        'id'         => (string) $m->user->id,
                        'first_name' => $m->user->first_name,
                        'last_name'  => $m->user->last_name,
                        'email'      => $m->user->email,
                        'username'   => $m->user->username ?? null,
                    ] : null,
                ]),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ];
        });
    }

    public function show(WalletMovement $movement): JsonResponse
    {
        return $this->handleServiceJson(fn() => [
            'data' => $movement->load(['wallet.currency', 'user']),
        ]);
    }
}
