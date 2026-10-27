<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Services;

use App\Domains\Games\Trivia\Models\TriviaRoom;

/**
 * Servicio: Gestión de Puntuaciones por Equipos.
 *
 * Maneja:
 * - Cálculo de puntuación total por equipo
 * - Determinación de equipo ganador
 * - Leaderboard de equipos
 */
class TeamScoreService
{
    /**
     * Obtener distribución de premios por equipo.
     */
    public function calculatePrizeDistribution(string $roomId, float $totalPrize) : array
    {
        $room = TriviaRoom::find($roomId);
        if (! $room || ! $room->usesTeams()) {
            return [];
        }

        $teamScores = $this->calculateTeamScores($roomId);
        $distribution = [];

        // Distribución según posición
        $prizePercentages = [1 => 0.5, 2 => 0.3, 3 => 0.2];

        foreach ($teamScores as $index => $team) {
            $position = $index + 1;
            $percentage = $prizePercentages[$position] ?? 0;

            $distribution[] = [
                'team_id' => $team['team_id'],
                'team_name' => $team['team_name'],
                'position' => $position,
                'prize_amount' => $totalPrize * $percentage,
                'prize_percentage' => $percentage * 100,
            ];
        }

        return $distribution;
    }

    /**
     * Calcular puntuación total de todos los equipos en una sala.
     */
    public function calculateTeamScores(string $roomId) : array
    {
        $room = TriviaRoom::with('teams')->find($roomId);
        if (! $room || ! $room->usesTeams()) {
            return [];
        }

        $scores = [];
        foreach ($room->teams as $team) {
            $scores[] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'team_color' => $team->color,
                'team_icon' => $team->icon,
                'total_score' => $team->calculateTotalScore(),
                'correct_answers' => $team->countCorrectAnswers(),
                'player_count' => $team->current_players,
            ];
        }

        // Ordenar por puntuación descendente
        usort($scores, fn ($a, $b) => $b['total_score'] <=> $a['total_score']);

        // Agregar posición
        foreach ($scores as $index => &$score) {
            $score['position'] = $index + 1;
        }

        return $scores;
    }

    /**
     * Determinar el equipo ganador.
     * Para duelo: equipo con más respuestas correctas
     * Para equipos: equipo con más puntos
     */
    public function determineWinningTeam(string $roomId) : ?array
    {
        $room = TriviaRoom::find($roomId);
        if (! $room || ! $room->usesTeams()) {
            return null;
        }

        $teamScores = $this->calculateTeamScores($roomId);
        if (empty($teamScores)) {
            return null;
        }

        // En duelo, desempatar por respuestas correctas si hay empate
        if ($room->isDuelMode() && count($teamScores) >= 2) {
            $first = $teamScores[0];
            $second = $teamScores[1];

            // Si hay empate en puntos, usar respuestas correctas
            if ($first['total_score'] === $second['total_score']) {
                return $this->determineDuelWinnerByCorrectAnswers($roomId, $teamScores);
            }
        }

        return $teamScores[0];
    }

    /**
     * Obtener leaderboard combinado: equipos + jugadores.
     */
    public function getCombinedLeaderboard(string $roomId, string $gameId) : array
    {
        $room = TriviaRoom::with(['teams.participants'])->find($roomId);
        if (! $room) {
            return [];
        }

        // Sin modo de equipos asignado, o asignado explícitamente a FREE_FOR_ALL: leaderboard individual
        if (in_array($room->gameModeCode(), [null, 'FREE_FOR_ALL'], true)) {
            $scoreService = app(ScoreService::class);
            $leaderboard = $scoreService->getLeaderboard($gameId);

            return [
                'mode' => 'free_for_all',
                'teams' => [],
                'players' => array_map(fn ($s) => $s->toArray(), $leaderboard->scores),
            ];
        }

        // Para modos con equipos
        $teamScores = $this->calculateTeamScores($roomId);
        $playersByTeam = [];

        foreach ($room->teams as $team) {
            $playersByTeam[$team->id] = [];
            foreach ($team->participants as $participant) {
                $playersByTeam[$team->id][] = [
                    'user_id' => $participant->user_id,
                    'total_score' => $participant->total_score,
                    'correct_answers' => $participant->correct_answers,
                    'status' => $participant->status,
                ];
            }

            // Ordenar jugadores del equipo por puntuación
            usort($playersByTeam[$team->id], fn ($a, $b) => $b['total_score'] <=> $a['total_score']);
        }

        return [
            'mode' => $room->gameModeCode(),
            'teams' => array_map(fn ($t) => array_merge($t, [
                'players' => $playersByTeam[$t['team_id']] ?? [],
            ]), $teamScores),
            'winning_team' => $teamScores[0] ?? null,
        ];
    }

    /**
     * Determinar ganador de duelo por respuestas correctas.
     */
    private function determineDuelWinnerByCorrectAnswers(string $roomId, array $teamScores) : array
    {
        $room = TriviaRoom::with('teams')->find($roomId);

        // Recalcular solo por respuestas correctas
        $byCorrectAnswers = [];
        foreach ($room->teams as $team) {
            $byCorrectAnswers[] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'team_color' => $team->color,
                'correct_answers' => $team->countCorrectAnswers(),
                'total_score' => $team->calculateTotalScore(),
            ];
        }

        // Ordenar por respuestas correctas descendente
        usort($byCorrectAnswers, fn ($a, $b) => $b['correct_answers'] <=> $a['correct_answers']);

        return $byCorrectAnswers[0];
    }
}
