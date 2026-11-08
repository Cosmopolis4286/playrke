<?php

namespace App\Http\Controllers\Games\Bingo\Payment;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Services\Winner\BingoPrizeReviewService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Acciones sobre premios de Bingo en revisión (suspendidos o con reintentos
 * agotados) — ver BingoPrizeReviewService. La cola se muestra en la página de
 * revisión de pagos (BingoCardPaymentReviewController::index), solo a admin.
 */
final class BingoPrizeReviewController extends Controller
{
    public function __construct(
        private readonly BingoPrizeReviewService $reviewService,
    ) {
    }

    public function cancel(Request $request, BingoCardPrize $prize) : JsonResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']]);

        return $this->handleServiceJson(fn () => [
            'data' => ['prize_status' => $this->reviewService->cancel((string) $prize->id, (string) Auth::id(), trim($validated['reason']))->prize_status],
        ]);
    }

    public function deliver(Request $request, BingoCardPrize $prize) : JsonResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'min:3', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->handleServiceJson(fn () => [
            'data' => ['prize_status' => $this->reviewService->markDelivered((string) $prize->id, (string) Auth::id(), trim($validated['reference']), $validated['notes'] ?? null)->prize_status],
        ]);
    }

    public function retry(BingoCardPrize $prize) : JsonResponse
    {
        return $this->handleServiceJson(fn () => [
            'data' => ['prize_status' => $this->reviewService->retry((string) $prize->id, (string) Auth::id())->prize_status],
        ]);
    }
}
