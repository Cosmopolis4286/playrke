<?php

use App\Models\Games\Common\Utils\GameStatus;
use Illuminate\Database\Migrations\Migration;

/**
 * Garantiza que los códigos de estado de juego críticos existan en la tabla
 * game_status, independientemente de si el seeder fue ejecutado.
 *
 * BingoDrawService::closeRoomIfFinalRound() requiere GameStatus::codeToId('FINISHED')
 * para cerrar la sala al terminar el sorteo. Si la fila no existe, la sala queda
 * bloqueada en ACTIVE/IN_PROGRESS y el cron vuelve a despachar el sorteo en cada
 * minuto, generando pagos duplicados.
 */
return new class extends Migration
{
    public function up(): void
    {
        $required = [
            ['code' => 'ACTIVE',      'label' => 'Activa'],
            ['code' => 'INACTIVE',    'label' => 'Inactiva'],
            ['code' => 'DELETED',     'label' => 'Eliminada'],
            ['code' => 'CLOSED',      'label' => 'Cerrada'],
            ['code' => 'IN_PROGRESS', 'label' => 'En Juego'],
            ['code' => 'FINISHED',    'label' => 'Finalizada'],
            ['code' => 'REFUND',      'label' => 'Reembolso'],
            ['code' => 'OFFLINE',     'label' => 'Fuera de Línea'],
            ['code' => 'CANCELLED',   'label' => 'Cancelada'],
        ];

        foreach ($required as $row) {
            GameStatus::updateOrCreate(['code' => $row['code']], ['label' => $row['label']]);
        }
    }

    public function down(): void
    {
        // No eliminar — estos registros son datos de referencia, no estructura.
    }
};
