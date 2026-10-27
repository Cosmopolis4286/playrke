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
     *
     * min_interval_seconds es el piso de seguridad al editar cron_expression
     * desde la UI (ver CronExpressionRule) — impide, ej., poner "cada minuto"
     * en un comando de mantenimiento pesado. Se fija en updateOrCreate() solo
     * si el registro es nuevo (no se pisa un valor ya ajustado en producción).
     */
    public function run() : void
    {
        $commands = [
            ['command' => 'check:expired-bingo-rooms', 'label' => 'Expirar salas de bingo vencidas', 'description' => 'Marca salas de bingo expiradas como INACTIVE o IN_PROGRESS.', 'min_interval_seconds' => 60],
            ['command' => 'bingo:start-pending-draws', 'label' => 'Iniciar sorteos de bingo pendientes', 'description' => 'Arranca el sorteo de salas de bingo que ya alcanzaron su hora de inicio.', 'min_interval_seconds' => 60],
            ['command' => 'bingo:handle-pending-payments-at-start', 'label' => 'Pagos de cartones pendientes al iniciar el sorteo', 'description' => 'Avisa a la comercializadora de pagos sin revisar antes del sorteo y, al iniciar, los rechaza con reembolso o los deja en revisión según su regla.', 'min_interval_seconds' => 60],
            ['command' => 'validate:transactions', 'label' => 'Validar transacciones', 'description' => 'Revisa transacciones pendientes de validación.', 'min_interval_seconds' => 60],
            ['command' => 'bingo:retry-failed-prize-payouts', 'label' => 'Reintentar pagos de premios de bingo fallidos', 'description' => 'Reintenta el pago de premios de bingo que fallaron en el primer intento.', 'min_interval_seconds' => 60],
            ['command' => 'bingo:retry-failed-refunds', 'label' => 'Reintentar reembolsos de bingo fallidos', 'description' => 'Reintenta reembolsos de bingo que fallaron en el primer intento.', 'min_interval_seconds' => 60],
            ['command' => 'generate:bingo-rooms', 'label' => 'Generar salas de bingo', 'description' => 'Genera nuevas salas de bingo automáticas según la configuración activa.', 'min_interval_seconds' => 300],
            ['command' => 'handle:exchange-rates', 'label' => 'Actualizar tasas de cambio', 'description' => 'Obtiene y actualiza las tasas de cambio oficiales.', 'min_interval_seconds' => 300],
            ['command' => 'exchange-rates:alerts', 'label' => 'Alertas de tasas de cambio', 'description' => 'Avisa a la red los cambios de tasa y a los dueños de tasas propias si están desactualizadas o se alejan de la oficial.', 'min_interval_seconds' => 300],
            ['command' => 'trivia:start-scheduled-rooms', 'label' => 'Iniciar salas de trivia programadas', 'description' => 'Arranca salas de trivia que ya alcanzaron su hora programada.', 'min_interval_seconds' => 60],
            ['command' => 'trivia:auto-finish-stale-games', 'label' => 'Finalizar partidas de trivia estancadas', 'description' => 'Cierra automáticamente partidas de trivia que quedaron sin avanzar.', 'min_interval_seconds' => 60],
            ['command' => 'trivia:retry-failed-prize-payouts', 'label' => 'Reintentar pagos de premios de trivia fallidos', 'description' => 'Reintenta el pago de premios de trivia que fallaron en el primer intento.', 'min_interval_seconds' => 60],
            ['command' => 'audit:prune', 'label' => 'Purgar auditoría antigua', 'description' => 'Elimina registros de auditoría más antiguos que el retention configurado.', 'min_interval_seconds' => 3600],
            ['command' => 'logs:prune', 'label' => 'Purgar logs antiguos', 'description' => 'Elimina logs del sistema más antiguos que la retención configurada.', 'min_interval_seconds' => 3600],
            ['command' => 'logs:alert-threshold', 'label' => 'Alertar por umbral de logs', 'description' => 'Revisa si la tasa de errores en logs supera el umbral configurado.', 'min_interval_seconds' => 300],
            ['command' => 'permissions:expire-specific-grants', 'label' => 'Expirar permisos individuales vencidos', 'description' => 'Revoca permisos individuales de usuario cuya fecha de expiración ya pasó, y notifica.', 'min_interval_seconds' => 60],
            ['command' => 'permissions:notify-expiring-specific-grants', 'label' => 'Avisar permisos individuales por vencer', 'description' => 'Notifica a superadmins los permisos individuales que expirarán pronto.', 'min_interval_seconds' => 3600],
            ['command' => 'suspensions:expire-temporary', 'label' => 'Reactivar suspensiones temporales vencidas', 'description' => 'Reactiva usuarios cuya suspensión temporal ya venció, sin esperar acción manual de un admin.', 'min_interval_seconds' => 60],
            ['command' => 'trivia:cancel-stale-rooms', 'label' => 'Cancelar salas de trivia abandonadas', 'description' => 'Cancela salas de trivia "waiting" abandonadas y reembolsa a los participantes confirmados.', 'min_interval_seconds' => 60],
            ['command' => 'kyc:notify-pending', 'label' => 'Avisar documentos KYC atrasados', 'description' => 'Notifica a superadmins los documentos KYC pendientes con más de 48h sin revisar.', 'min_interval_seconds' => 3600],
            ['command' => 'wallet:notify-stale-movements', 'label' => 'Avisar movimientos de billetera atrasados', 'description' => 'Notifica a tesorería los depósitos/retiros pendientes de aprobación con antigüedad excesiva.', 'min_interval_seconds' => 300],
            ['command' => 'notifications:prune', 'label' => 'Purgar notificaciones antiguas', 'description' => 'Elimina notificaciones in-app ya leídas más antiguas que la retención configurada.', 'min_interval_seconds' => 3600],
        ];

        foreach ($commands as $data) {
            $scheduled = ScheduledCommand::firstOrNew(['command' => $data['command']]);
            $scheduled->label = $data['label'];
            $scheduled->description = $data['description'];

            if (! $scheduled->exists) {
                $scheduled->min_interval_seconds = $data['min_interval_seconds'];
            }

            $scheduled->save();
        }

        $this->command->info('✔ ScheduledCommandSeeder: ' . count($commands) . ' comandos programados procesados.');
    }
}
