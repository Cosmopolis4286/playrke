<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Services;

use Illuminate\Support\Facades\DB;

/**
 * Estadísticas de una pregunta ya respondida: cuántos jugadores eligieron
 * cada opción y cuántos respondieron en total. Se muestran a toda la sala
 * en la fase de revelación, así que solo deben consultarse cuando la
 * pregunta está cerrada (antes revelarían hacia dónde va la mayoría).
 */
final class TriviaQuestionStats
{
    /**
     * @return array{distribution: array<int, int>, answered: int}
     */
    public static function forQuestion(string $gameId, string $questionId, int $optionCount) : array
    {
        $rows = DB::connection('pgsql_trivia')
            ->table('trivia_answers')
            ->where('game_id', $gameId)
            ->where('question_id', $questionId)
            ->whereNull('deleted_at')
            ->selectRaw('selected_option_index, count(*) as total')
            ->groupBy('selected_option_index')
            ->pluck('total', 'selected_option_index');

        $distribution = array_fill(0, max(0, $optionCount), 0);
        $answered = 0;

        foreach ($rows as $index => $total) {
            $answered += (int) $total;
            $index = (int) $index;

            // -1 = sin respuesta a tiempo o pregunta saltada: cuenta como
            // participación, pero no suma a ninguna opción.
            if ($index >= 0 && $index < count($distribution)) {
                $distribution[$index] = (int) $total;
            }
        }

        return ['distribution' => $distribution, 'answered' => $answered];
    }
}
