<?php

namespace App\DTOs\Roles\Player\Dashboard;

use App\DTOs\ContextDTO;

final class PlayerDashboardContextDTO implements ContextDTO
{
    public function __construct(
        /** Nombre del usuario autenticado */
        public readonly string $userName,
        /** Stats principales del jugador */
        public readonly array $stats,
        /** Actividad reciente (cartones comprados) */
        public readonly array $recentActivity,
        /** Premios ganados con detalle (monto, patrón, sala) */
        public readonly array $prizesWon,
        /** Participaciones recientes en Trivia */
        public readonly array $triviaActivity,
        /** Estado del documento KYC propio (null si nunca se envió) */
        public readonly ?array $kycStatus,
        /** Período activo (e.g. "Marzo 2026") */
        public readonly string $period,
    ) {}

    public function toView(): array
    {
        return [
            'userName'       => $this->userName,
            'stats'          => $this->stats,
            'recentActivity' => $this->recentActivity,
            'prizesWon'      => $this->prizesWon,
            'triviaActivity' => $this->triviaActivity,
            'kycStatus'      => $this->kycStatus,
            'period'         => $this->period,
        ];
    }
}
