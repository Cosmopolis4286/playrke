<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BingoRoom;
use App\Models\BingoCtns;
use App\Models\BingoDraw;
use App\Models\PlayHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BingoController extends Controller
{
    /**
     * Get list of bingo rooms
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRooms(Request $request): JsonResponse
    {
        $query = BingoRoom::query()
            ->with(['currency', 'creator'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by currency
        if ($request->has('currency')) {
            $query->where('currency_id', $request->currency);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $rooms = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'rooms' => $rooms->map(function ($room) {
                return [
                    'id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'status' => $room->status,
                    'type' => $room->type,
                    'currency' => $room->currency->code ?? 'USD',
                    'card_price' => $room->card_price,
                    'max_players' => $room->max_players,
                    'current_players' => $room->current_players ?? 0,
                    'prize_pool' => $room->prize_pool,
                    'start_time' => $room->start_time,
                    'created_at' => $room->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
            ],
        ]);
    }

    /**
     * Get single room details
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getRoom(int $id): JsonResponse
    {
        $room = BingoRoom::with(['currency', 'creator', 'players'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'description' => $room->description,
                'status' => $room->status,
                'type' => $room->type,
                'currency' => $room->currency->code ?? 'USD',
                'card_price' => $room->card_price,
                'max_players' => $room->max_players,
                'current_players' => $room->players->count(),
                'prize_pool' => $room->prize_pool,
                'start_time' => $room->start_time,
                'patterns' => $room->patterns ?? [],
                'rules' => $room->rules ?? [],
                'created_at' => $room->created_at,
            ],
        ]);
    }

    /**
     * Purchase bingo cards
     *
     * @param Request $request
     * @param int $roomId
     * @return JsonResponse
     */
    public function purchaseCards(Request $request, int $roomId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $room = BingoRoom::findOrFail($roomId);
        $user = $request->user();

        // Calculate total cost
        $totalCost = $room->card_price * $validated['quantity'];

        // Check user balance
        $wallet = $user->wallets()->where('currency_id', $room->currency_id)->first();

        if (!$wallet || $wallet->balance < $totalCost) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo insuficiente',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Deduct from wallet
            $wallet->decrement('balance', $totalCost);

            // Generate bingo cards
            $cards = [];
            for ($i = 0; $i < $validated['quantity']; $i++) {
                $card = $this->generateBingoCard();

                // Store card in database
                $bingoCard = BingoCtns::create([
                    'room_id' => $roomId,
                    'user_id' => $user->id,
                    'numbers' => json_encode($card),
                    'status' => 'active',
                ]);

                $cards[] = [
                    'id' => $bingoCard->id,
                    'numbers' => $card,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cartones comprados exitosamente',
                'cards' => $cards,
                'total_cost' => $totalCost,
                'new_balance' => $wallet->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al comprar cartones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get player's cards for a room
     *
     * @param int $roomId
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlayerCards(int $roomId, Request $request): JsonResponse
    {
        $user = $request->user();

        $cards = BingoCtns::where('room_id', $roomId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        return response()->json([
            'success' => true,
            'cards' => $cards->map(function ($card) {
                return [
                    'id' => $card->id,
                    'numbers' => json_decode($card->numbers, true),
                    'marked' => json_decode($card->marked ?? '[]', true),
                ];
            }),
        ]);
    }

    /**
     * Get current draw for a room
     *
     * @param int $roomId
     * @return JsonResponse
     */
    public function getDraw(int $roomId): JsonResponse
    {
        $draw = BingoDraw::where('room_id', $roomId)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$draw) {
            return response()->json([
                'success' => false,
                'message' => 'No hay sorteo activo',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'draw' => [
                'id' => $draw->id,
                'room_id' => $draw->room_id,
                'current_number' => $draw->current_number,
                'status' => $draw->status,
                'started_at' => $draw->started_at,
            ],
        ]);
    }

    /**
     * Get called numbers for a draw
     *
     * @param int $drawId
     * @return JsonResponse
     */
    public function getCalledNumbers(int $drawId): JsonResponse
    {
        $draw = BingoDraw::findOrFail($drawId);

        $calledNumbers = json_decode($draw->called_numbers ?? '[]', true);

        return response()->json([
            'success' => true,
            'called_numbers' => $calledNumbers,
            'current_number' => $draw->current_number,
        ]);
    }

    /**
     * Check if player has won
     *
     * @param Request $request
     * @param int $cardId
     * @return JsonResponse
     */
    public function checkWinner(Request $request, int $cardId): JsonResponse
    {
        $card = BingoCtns::findOrFail($cardId);
        $user = $request->user();

        // Verify card belongs to user
        if ($card->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Este cartón no te pertenece',
            ], 403);
        }

        // TODO: Implement actual winner validation logic
        // This would check if the marked numbers form a winning pattern

        $isWinner = false; // Placeholder

        return response()->json([
            'success' => true,
            'is_winner' => $isWinner,
        ]);
    }

    /**
     * Get winners for a room
     *
     * @param int $roomId
     * @return JsonResponse
     */
    public function getWinners(int $roomId): JsonResponse
    {
        // TODO: Get actual winners from database
        $winners = [];

        return response()->json([
            'success' => true,
            'winners' => $winners,
        ]);
    }

    /**
     * Get player's game history
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlayerHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = PlayHistory::where('user_id', $user->id)
            ->with(['room', 'draw'])
            ->orderBy('created_at', 'desc');

        // Filter by date range
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        // Filter by status (won/lost)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $history = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'history' => $history->map(function ($item) {
                return [
                    'id' => $item->id,
                    'room_name' => $item->room->name ?? 'N/A',
                    'date' => $item->created_at,
                    'cards_purchased' => $item->cards_purchased,
                    'total_spent' => $item->total_spent,
                    'prize_won' => $item->prize_won ?? 0,
                    'status' => $item->status,
                ];
            }),
            'pagination' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    /**
     * Notify prize payment
     *
     * @param Request $request
     * @param int $historyId
     * @return JsonResponse
     */
    public function notifyPrizePayment(Request $request, int $historyId): JsonResponse
    {
        // TODO: Implement prize payment notification logic

        return response()->json([
            'success' => true,
            'message' => 'Notificación de pago enviada',
        ]);
    }

    /**
     * Generate a bingo card with random numbers
     *
     * @return array
     */
    private function generateBingoCard(): array
    {
        $card = [];

        // Standard 5x5 bingo card
        for ($col = 0; $col < 5; $col++) {
            $column = [];
            $min = ($col * 15) + 1;
            $max = ($col + 1) * 15;

            $numbers = range($min, $max);
            shuffle($numbers);

            for ($row = 0; $row < 5; $row++) {
                // Center is FREE space
                if ($col === 2 && $row === 2) {
                    $column[] = 0; // 0 represents FREE
                } else {
                    $column[] = array_pop($numbers);
                }
            }

            $card[] = $column;
        }

        return $card;
    }
}
