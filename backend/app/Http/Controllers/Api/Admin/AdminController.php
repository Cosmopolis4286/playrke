<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BingoRoom;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    /**
     * Get dashboard stats
     *
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        // TODO: Calculate real stats
        $stats = [
            'total_users' => User::count(),
            'active_rooms' => BingoRoom::where('status', 'active')->count(),
            'pending_transactions' => Transaction::where('status', 'pending')->count(),
            'today_revenue' => 0,
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    // ====================================
    // CUSTOMERS CRUD
    // ====================================

    public function getCustomers(Request $request): JsonResponse
    {
        $query = User::role('customer')
            ->with(['roles', 'country'])
            ->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'customers' => $customers->map(fn($user) => $this->formatUser($user)),
            'pagination' => $this->formatPagination($customers),
        ]);
    }

    public function getCustomer(int $id): JsonResponse
    {
        $customer = User::role('customer')
            ->with(['roles', 'country', 'wallets'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'customer' => $this->formatUser($customer, true),
        ]);
    }

    public function createCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'unique:users'],
            'email' => ['required', 'email', 'unique:users'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'password' => ['required', Password::defaults()],
            'country_id' => ['required', 'exists:countries,id'],
        ]);

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole('customer');

        return response()->json([
            'success' => true,
            'message' => 'Cliente creado exitosamente',
            'customer' => $this->formatUser($user->fresh(['roles', 'country'])),
        ], 201);
    }

    public function updateCustomer(int $id, Request $request): JsonResponse
    {
        $customer = User::role('customer')->findOrFail($id);

        $validated = $request->validate([
            'email' => ['sometimes', 'email', 'unique:users,email,' . $id],
            'first_name' => ['sometimes', 'string'],
            'last_name' => ['sometimes', 'string'],
            'password' => ['sometimes', Password::defaults()],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado exitosamente',
            'customer' => $this->formatUser($customer->fresh(['roles', 'country'])),
        ]);
    }

    public function deleteCustomer(int $id): JsonResponse
    {
        $customer = User::role('customer')->findOrFail($id);
        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado exitosamente',
        ]);
    }

    // ====================================
    // TREASURERS CRUD
    // ====================================

    public function getTreasurers(Request $request): JsonResponse
    {
        $query = User::role('treasurer')
            ->with(['roles', 'country'])
            ->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $treasurers = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'treasurers' => $treasurers->map(fn($user) => $this->formatUser($user)),
            'pagination' => $this->formatPagination($treasurers),
        ]);
    }

    public function getTreasurer(int $id): JsonResponse
    {
        $treasurer = User::role('treasurer')
            ->with(['roles', 'country'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'treasurer' => $this->formatUser($treasurer, true),
        ]);
    }

    public function createTreasurer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'unique:users'],
            'email' => ['required', 'email', 'unique:users'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'password' => ['required', Password::defaults()],
            'country_id' => ['required', 'exists:countries,id'],
        ]);

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole('treasurer');

        return response()->json([
            'success' => true,
            'message' => 'Tesorero creado exitosamente',
            'treasurer' => $this->formatUser($user->fresh(['roles', 'country'])),
        ], 201);
    }

    public function updateTreasurer(int $id, Request $request): JsonResponse
    {
        $treasurer = User::role('treasurer')->findOrFail($id);

        $validated = $request->validate([
            'email' => ['sometimes', 'email', 'unique:users,email,' . $id],
            'first_name' => ['sometimes', 'string'],
            'last_name' => ['sometimes', 'string'],
            'password' => ['sometimes', Password::defaults()],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $treasurer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tesorero actualizado exitosamente',
            'treasurer' => $this->formatUser($treasurer->fresh(['roles', 'country'])),
        ]);
    }

    public function deleteTreasurer(int $id): JsonResponse
    {
        $treasurer = User::role('treasurer')->findOrFail($id);
        $treasurer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tesorero eliminado exitosamente',
        ]);
    }

    // ====================================
    // SELLERS CRUD
    // ====================================

    public function getSellers(Request $request): JsonResponse
    {
        $query = User::role('seller')
            ->with(['roles', 'country'])
            ->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sellers = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'sellers' => $sellers->map(fn($user) => $this->formatUser($user)),
            'pagination' => $this->formatPagination($sellers),
        ]);
    }

    public function getSeller(int $id): JsonResponse
    {
        $seller = User::role('seller')
            ->with(['roles', 'country'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'seller' => $this->formatUser($seller, true),
        ]);
    }

    public function createSeller(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'unique:users'],
            'email' => ['required', 'email', 'unique:users'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'password' => ['required', Password::defaults()],
            'country_id' => ['required', 'exists:countries,id'],
        ]);

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole('seller');

        return response()->json([
            'success' => true,
            'message' => 'Vendedor creado exitosamente',
            'seller' => $this->formatUser($user->fresh(['roles', 'country'])),
        ], 201);
    }

    public function updateSeller(int $id, Request $request): JsonResponse
    {
        $seller = User::role('seller')->findOrFail($id);

        $validated = $request->validate([
            'email' => ['sometimes', 'email', 'unique:users,email,' . $id],
            'first_name' => ['sometimes', 'string'],
            'last_name' => ['sometimes', 'string'],
            'password' => ['sometimes', Password::defaults()],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $seller->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendedor actualizado exitosamente',
            'seller' => $this->formatUser($seller->fresh(['roles', 'country'])),
        ]);
    }

    public function deleteSeller(int $id): JsonResponse
    {
        $seller = User::role('seller')->findOrFail($id);
        $seller->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vendedor eliminado exitosamente',
        ]);
    }

    // ====================================
    // ROOMS MANAGEMENT
    // ====================================

    public function getRooms(Request $request): JsonResponse
    {
        $query = BingoRoom::with(['currency', 'creator'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $rooms = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'rooms' => $rooms->map(fn($room) => $this->formatRoom($room)),
            'pagination' => $this->formatPagination($rooms),
        ]);
    }

    public function getRoom(int $id): JsonResponse
    {
        $room = BingoRoom::with(['currency', 'creator', 'players'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'room' => $this->formatRoom($room, true),
        ]);
    }

    public function createRoom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'card_price' => ['required', 'numeric', 'min:0'],
            'max_players' => ['required', 'integer', 'min:1'],
            'prize_pool' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'in:classic,speed,progressive'],
        ]);

        $room = BingoRoom::create([
            ...$validated,
            'creator_id' => auth()->id(),
            'status' => 'waiting',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sala creada exitosamente',
            'room' => $this->formatRoom($room->fresh(['currency', 'creator'])),
        ], 201);
    }

    public function updateRoom(int $id, Request $request): JsonResponse
    {
        $room = BingoRoom::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'card_price' => ['sometimes', 'numeric', 'min:0'],
            'max_players' => ['sometimes', 'integer', 'min:1'],
            'prize_pool' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $room->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sala actualizada exitosamente',
            'room' => $this->formatRoom($room->fresh(['currency', 'creator'])),
        ]);
    }

    public function deleteRoom(int $id): JsonResponse
    {
        $room = BingoRoom::findOrFail($id);

        if ($room->status === 'playing') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una sala en juego',
            ], 422);
        }

        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sala eliminada exitosamente',
        ]);
    }

    public function startGame(int $id): JsonResponse
    {
        $room = BingoRoom::findOrFail($id);

        if ($room->status !== 'waiting') {
            return response()->json([
                'success' => false,
                'message' => 'La sala no está en estado de espera',
            ], 422);
        }

        $room->update(['status' => 'playing']);

        // TODO: Initialize game draw, broadcast event, etc.

        return response()->json([
            'success' => true,
            'message' => 'Juego iniciado exitosamente',
            'room' => $this->formatRoom($room->fresh()),
        ]);
    }

    // ====================================
    // TRANSACTIONS
    // ====================================

    public function getTransactions(Request $request): JsonResponse
    {
        $query = Transaction::with(['user', 'wallet.currency'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'transactions' => $transactions->map(fn($t) => $this->formatTransaction($t)),
            'pagination' => $this->formatPagination($transactions),
        ]);
    }

    public function getTransaction(int $id): JsonResponse
    {
        $transaction = Transaction::with(['user', 'wallet.currency'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'transaction' => $this->formatTransaction($transaction, true),
        ]);
    }

    public function approveTransaction(int $id, Request $request): JsonResponse
    {
        $transaction = Transaction::findOrFail($id);

        if ($transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden aprobar transacciones pendientes',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $transaction->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // If deposit, credit wallet
            if ($transaction->type === 'deposit') {
                $transaction->wallet->increment('balance', $transaction->amount);
            }

            // If withdrawal, debit wallet (should already be reserved)
            if ($transaction->type === 'withdrawal') {
                $transaction->wallet->decrement('balance', $transaction->amount);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transacción aprobada exitosamente',
                'transaction' => $this->formatTransaction($transaction->fresh()),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar transacción',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function rejectTransaction(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string'],
        ]);

        $transaction = Transaction::findOrFail($id);

        if ($transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden rechazar transacciones pendientes',
            ], 422);
        }

        $transaction->update([
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $validated['reason'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transacción rechazada',
            'transaction' => $this->formatTransaction($transaction->fresh()),
        ]);
    }

    // ====================================
    // REPORTS
    // ====================================

    public function getReports(Request $request): JsonResponse
    {
        // TODO: Implement real reporting logic

        $reports = [
            'revenue' => [],
            'players' => [],
            'games' => [],
        ];

        return response()->json([
            'success' => true,
            'reports' => $reports,
        ]);
    }

    public function exportReport(Request $request): JsonResponse
    {
        // TODO: Implement export functionality

        return response()->json([
            'success' => true,
            'message' => 'Reporte exportado',
            'download_url' => '/downloads/report.csv',
        ]);
    }

    // ====================================
    // SETTINGS
    // ====================================

    public function getSettings(): JsonResponse
    {
        // TODO: Get from database

        $settings = [
            'general' => [
                'site_name' => 'PlayRKE Bingo',
                'contact_email' => 'support@playrke.com',
                'timezone' => 'America/Caracas',
                'language' => 'es',
            ],
            'game' => [
                'number_call_interval' => 5,
                'max_cards_per_user' => 10,
                'auto_mark_enabled' => true,
                'sound_enabled' => true,
            ],
            'payment' => [
                'min_deposit' => 10,
                'min_withdrawal' => 20,
                'withdrawal_fee' => 0,
                'auto_approve_deposits' => false,
            ],
        ];

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        // TODO: Save to database

        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada exitosamente',
        ]);
    }

    // ====================================
    // HELPER METHODS
    // ====================================

    private function formatUser($user, $detailed = false): array
    {
        $data = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => "{$user->first_name} {$user->last_name}",
            'role' => $user->roles->first()?->prefix,
            'country' => $user->country?->name,
            'created_at' => $user->created_at,
        ];

        if ($detailed) {
            $data['wallets'] = $user->wallets?->map(function ($wallet) {
                return [
                    'currency' => $wallet->currency->code,
                    'balance' => $wallet->balance,
                ];
            });
        }

        return $data;
    }

    private function formatRoom($room, $detailed = false): array
    {
        $data = [
            'id' => $room->id,
            'name' => $room->name,
            'description' => $room->description,
            'status' => $room->status,
            'type' => $room->type,
            'currency' => $room->currency->code ?? 'USD',
            'card_price' => $room->card_price,
            'max_players' => $room->max_players,
            'prize_pool' => $room->prize_pool,
            'created_at' => $room->created_at,
        ];

        if ($detailed) {
            $data['creator'] = $room->creator?->username;
            $data['current_players'] = $room->players?->count() ?? 0;
        }

        return $data;
    }

    private function formatTransaction($transaction, $detailed = false): array
    {
        $data = [
            'id' => $transaction->id,
            'user' => $transaction->user?->username,
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'currency' => $transaction->wallet->currency->code ?? 'USD',
            'status' => $transaction->status,
            'created_at' => $transaction->created_at,
        ];

        if ($detailed) {
            $data['method'] = $transaction->method;
            $data['reference'] = $transaction->reference;
            $data['account_info'] = json_decode($transaction->account_info ?? '{}', true);
        }

        return $data;
    }

    private function formatPagination($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
