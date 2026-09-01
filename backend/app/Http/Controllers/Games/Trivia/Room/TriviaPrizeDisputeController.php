<?php

declare(strict_types=1);

namespace App\Http\Controllers\Games\Trivia\Room;

use App\Http\Controllers\Controller;
use App\Models\Games\Trivia\TriviaParticipant;
use App\Services\Games\GamePrizeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Disputa un premio de Trivia ya pagado ('claimed') y revierte el débito de
 * la wallet del ganador — ver GamePrizeService::disputeAndReverse().
 * Disparador manual únicamente (2026-08-31): solo admin/superadmin, sin
 * señal automática de anti-fraude conectada todavía.
 * Ruta: POST /trivia-rooms/{roomId}/participants/{participantId}/dispute
 */
final class TriviaPrizeDisputeController extends Controller
{
    public function __construct(
        private readonly GamePrizeService $gamePrizeService,
    ) {}

    public function store(Request $request, string $roomId, string $participantId): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $participant = TriviaParticipant::where('id', $participantId)
            ->where('room_id', $roomId)
            ->firstOrFail();

        try {
            $this->gamePrizeService->disputeAndReverse($participant, $validated['reason']);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Premio revertido y marcado en disputa.');
    }
}
