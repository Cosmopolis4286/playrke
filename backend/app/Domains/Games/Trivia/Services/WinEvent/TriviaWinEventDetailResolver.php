<?php

namespace App\Domains\Games\Trivia\Services\WinEvent;

use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Platform\WinEvent\Contracts\WinEventDetailResolver;
use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Domains\Platform\WinEvent\Support\WinEventDetailSection as Section;
use App\Domains\Wallet\Models\WalletMovement;
use App\Models\Games\Trivia\TriviaParticipant;
use App\Models\User;

/**
 * Detalle de una ganancia de Trivia. PrizeDistributionService::announceTopWinner()
 * reporta el evento con source_reference = wallet_movements.id del pago y
 * metadata {room_id, room_code}. Sala y participantes viven en pgsql_trivia;
 * usuarios y movimientos en MySQL — por eso no hay eager loading cruzado.
 */
final class TriviaWinEventDetailResolver implements WinEventDetailResolver
{
    private const RANKING_LIMIT = 10;

    private const PRIZE_STATUS_VARIANTS = [
        'waiting'    => 'warning',
        'processing' => 'info',
        'claimed'    => 'success',
        'cancelled'  => 'secondary',
        'suspended'  => 'danger',
        'disputed'   => 'danger',
    ];

    public function gameSlug(): string
    {
        return 'trivia';
    }

    public function consumedMetadataKeys(): array
    {
        return ['room_id', 'room_code'];
    }

    public function resolve(WinEvent $winEvent): array
    {
        $roomId = $winEvent->metadata['room_id'] ?? null;
        $room = $roomId ? TriviaRoom::find($roomId) : null;

        $participant = $room && $winEvent->user_id
            ? TriviaParticipant::where('room_id', $room->id)->where('user_id', $winEvent->user_id)->first()
            : null;

        $movement = $winEvent->source_reference
            ? WalletMovement::find($winEvent->source_reference)
            : null;

        return array_values(array_filter([
            $participant ? $this->performanceSection($participant) : null,
            $room ? $this->roomSection($room, $winEvent) : null,
            $movement ? $this->movementSection($movement) : null,
        ]));
    }

    private function performanceSection(TriviaParticipant $p): Section
    {
        return Section::make('trivia-performance', 'Desempeño del ganador', 'fas fa-brain')
            ->item('Posición final', $p->final_rank ? "#{$p->final_rank}" : null, Section::TYPE_BADGE, 'success')
            ->item('Respuestas correctas', $p->correct_answers)
            ->item('Respuestas incorrectas', $p->wrong_answers)
            ->item('Puntaje total', $p->total_score)
            ->item('Tiempo total (s)', $p->total_time !== null ? number_format((float) $p->total_time, 2) : null)
            ->item('Premio ganado', $p->prize_won !== null ? number_format((float) $p->prize_won, 2) : null, Section::TYPE_MONEY)
            ->item('Estado del premio', $p->prize_status, Section::TYPE_BADGE, self::PRIZE_STATUS_VARIANTS[$p->prize_status] ?? 'secondary');
    }

    private function roomSection(TriviaRoom $room, WinEvent $winEvent): Section
    {
        $ranked = TriviaParticipant::where('room_id', $room->id)
            ->whereNotNull('final_rank')
            ->orderBy('final_rank')
            ->limit(self::RANKING_LIMIT)
            ->get(['user_id', 'final_rank', 'correct_answers', 'total_score', 'prize_won']);

        $names = User::whereIn('id', $ranked->pluck('user_id')->filter()->all())
            ->get(['id', 'first_name', 'last_name', 'username'])
            ->mapWithKeys(fn (User $u) => [(string) $u->id => $u->full_name ?: $u->username]);

        return Section::make('trivia-room', 'Sala de Trivia', 'fas fa-door-open')
            ->item('Título', $room->title)
            ->item('Código', $room->code, Section::TYPE_CODE)
            ->item('Categoría', $room->category)
            ->item('Estado', $room->status, Section::TYPE_BADGE, 'secondary')
            ->item('Modo', $room->game_mode)
            ->item('Modelo de premio', $room->prize_model)
            ->item('Preguntas', $room->question_count)
            ->item('Jugadores', $room->current_players)
            ->item('Inicio', $room->started_at?->toISOString(), Section::TYPE_DATETIME)
            ->item('Fin', $room->ended_at?->toISOString(), Section::TYPE_DATETIME)
            ->table('Ranking final', [
                'rank'    => '#',
                'player'  => 'Jugador',
                'correct' => 'Correctas',
                'score'   => 'Puntaje',
                'prize'   => 'Premio',
            ], $ranked->map(fn (TriviaParticipant $p) => [
                'rank'       => $p->final_rank,
                'player'     => $names[(string) $p->user_id] ?? (string) $p->user_id,
                'correct'    => $p->correct_answers,
                'score'      => $p->total_score,
                'prize'      => $p->prize_won !== null ? number_format((float) $p->prize_won, 2) : '—',
                '_highlight' => (string) $p->user_id === (string) $winEvent->user_id,
            ])->all());
    }

    private function movementSection(WalletMovement $m): Section
    {
        return Section::make('trivia-movement', 'Movimiento de billetera', 'fas fa-exchange-alt')
            ->item('Tipo', $m->type, Section::TYPE_BADGE, 'info')
            ->item('Estado', $m->status, Section::TYPE_BADGE, $m->status === WalletMovement::STATUS_COMPLETED ? 'success' : 'warning')
            ->item('Monto', number_format((float) $m->amount, 2), Section::TYPE_MONEY)
            ->item('Saldo antes', $m->balance_before !== null ? number_format((float) $m->balance_before, 2) : null)
            ->item('Saldo después', $m->balance_after !== null ? number_format((float) $m->balance_after, 2) : null)
            ->item('Descripción', $m->description)
            ->item('ID del movimiento', (string) $m->id, Section::TYPE_CODE);
    }
}
