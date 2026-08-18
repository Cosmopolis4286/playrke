<?php

namespace Database\Seeders;

use App\Models\Commands\ScheduledCommand;
use Illuminate\Database\Seeder;

class ScheduledCommandSeeder extends Seeder
{
    /**
     * Un registro por cada Schedule::command() de routes/console.php — debe
     * mantenerse en sincronía manual con ese archivo (agregar un comando ahí
     * sin correr este seeder no rompe nada, ScheduledCommandGate asume
     * "activo" por defecto para comandos sin registro).
     */
    public function run(): void
    {
        $commands = [
            ['command' => 'check:expired-bingo-rooms', 'label' => 'Expirar salas de bingo vencidas', 'description' => 'Marca salas de bingo expiradas como INACTIVE o IN_PROGRESS.'],
            ['command' => 'bingo:start-pending-draws', 'label' => 'Iniciar sorteos de bingo pendientes', 'description' => 'Arranca el sorteo de salas de bingo que ya alcanzaron su hora de inicio.'],
            ['command' => 'validate:transactions', 'label' => 'Validar transacciones', 'description' => 'Revisa transacciones pendientes de validación.'],
            ['command' => 'bingo:retry-failed-prize-payouts', 'label' => 'Reintentar pagos de premios de bingo fallidos', 'description' => 'Reintenta el pago de premios de bingo que fallaron en el primer intento.'],
            ['command' => 'bingo:retry-failed-refunds', 'label' => 'Reintentar reembolsos de bingo fallidos', 'description' => 'Reintenta reembolsos de bingo que fallaron en el primer intento.'],
            ['command' => 'generate:bingo-rooms', 'label' => 'Generar salas de bingo', 'description' => 'Genera nuevas salas de bingo automáticas según la configuración activa.'],
            ['command' => 'handle:exchange-rates', 'label' => 'Actualizar tasas de cambio', 'description' => 'Obtiene y actualiza las tasas de cambio oficiales.'],
            ['command' => 'trivia:start-scheduled-rooms', 'label' => 'Iniciar salas de trivia programadas', 'description' => 'Arranca salas de trivia que ya alcanzaron su hora programada.'],
            ['command' => 'trivia:auto-finish-stale-games', 'label' => 'Finalizar partidas de trivia estancadas', 'description' => 'Cierra automáticamente partidas de trivia que quedaron sin avanzar.'],
            ['command' => 'trivia:retry-failed-prize-payouts', 'label' => 'Reintentar pagos de premios de trivia fallidos', 'description' => 'Reintenta el pago de premios de trivia que fallaron en el primer intento.'],
            ['command' => 'audit:prune', 'label' => 'Purgar auditoría antigua', 'description' => 'Elimina registros de auditoría más antiguos que el retention configurado.'],
            ['command' => 'logs:prune', 'label' => 'Purgar logs antiguos', 'description' => 'Elimina logs del sistema más antiguos que la retención configurada.'],
            ['command' => 'logs:alert-threshold', 'label' => 'Alertar por umbral de logs', 'description' => 'Revisa si la tasa de errores en logs supera el umbral configurado.'],
            ['command' => 'permissions:expire-specific-grants', 'label' => 'Expirar permisos individuales vencidos', 'description' => 'Revoca permisos individuales de usuario cuya fecha de expiración ya pasó, y notifica.'],
            ['command' => 'permissions:notify-expiring-specific-grants', 'label' => 'Avisar permisos individuales por vencer', 'description' => 'Notifica a superadmins los permisos individuales que expirarán pronto.'],
        ];

        foreach ($commands as $data) {
            ScheduledCommand::updateOrCreate(
                ['command' => $data['command']],
                ['label' => $data['label'], 'description' => $data['description']]
            );
        }

        $this->command->info('✔ ScheduledCommandSeeder: ' . count($commands) . ' comandos programados procesados.');
    }
}
