<?php

namespace App\Services\Roles\SuperAdmin\Dashboard;

use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Models\Games\Trivia\TriviaParticipant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Resumen de actividad Trivia para SuperAdmin — el módulo existe como
 * negocio propio (comisión, entry_fee, premio fijo/acumulado) pero hoy solo
 * aparece como una línea suelta en el feed de actividad genérico. Mismo
 * patrón que RkeSummaryService (Cache::remember, TTL 90s).
 *
 * trivia_rooms/trivia_participants viven en la conexión 'pgsql_trivia'
 * (Postgres, separada de la conexión principal) — no se puede hacer
 * belongsTo(User) directo desde TriviaParticipant, así que el conteo de
 * participantes se resuelve agregando por room_id sin relación Eloquent.
 */
final class TriviaSummaryService
{
    private const CACHE_KEY = 'superadmin:trivia-summary';
    private const CACHE_TTL = 90;

    public function getSummary() : array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->buildSummary());
    }

    private function buildSummary() : array
    {
        $activeRooms = TriviaRoom::whereIn('status', ['waiting', 'playing'])->count();

        $participantCounts = TriviaParticipant::query()
            ->select('room_id', DB::raw('COUNT(*) as participant_count'))
            ->whereIn('status', ['confirmed', 'playing', 'winner'])
            ->groupBy('room_id')
            ->pluck('participant_count', 'room_id');

        $totalParticipants = (int) $participantCounts->sum();

        // entry_fee_usd/entry_fee no existen en la tabla real (desalineados
        // del $fillable del modelo — deuda técnica pre-existente, fuera de
        // alcance) — la comisión se reporta como % configurado sobre el
        // premio/pool, no un monto USD que requeriría un campo inexistente.
        $rooms = TriviaRoom::query()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'title', 'code', 'status', 'commission_percent', 'prize_type', 'prize_amount', 'pool_accumulated', 'created_at'])
            ->map(function(TriviaRoom $room) use ($participantCounts) {
                $participants = (int) ($participantCounts[$room->id] ?? 0);
                $prizeAmount = $room->prize_type === 'accumulated'
                    ? (float) $room->pool_accumulated
                    : (float) $room->prize_amount;
                $commissionAmount = round($prizeAmount * ((float) $room->commission_percent / 100), 2);

                return [
                    'room_id' => (string) $room->id,
                    'title' => $room->title ?? $room->code,
                    'status' => $room->status,
                    'participant_count' => $participants,
                    'commission_percent' => (float) $room->commission_percent,
                    'commission_amount' => $commissionAmount,
                    'prize_type' => $room->prize_type,
                    'prize_amount' => $prizeAmount,
                    'created_at' => $room->created_at?->toIso8601String(),
                ];
            })
            ->values();

        return [
            'totals' => [
                'active_rooms' => $activeRooms,
                'total_participants' => $totalParticipants,
                'total_commission' => round($rooms->sum('commission_amount'), 2),
            ],
            'rooms' => $rooms,
        ];
    }
}
