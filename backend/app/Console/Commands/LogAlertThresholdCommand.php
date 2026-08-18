<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\SystemLog;
use App\Services\Audit\LogAlertSettings;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Cuenta errores recientes en `system_logs` y alerta si se supera el umbral
 * configurado (log_alert_error_threshold / log_alert_window_minutes, ver
 * LogAlertSettings) — cierre natural de /logs: antes había que abrir la
 * pantalla manualmente para notar un pico de errores.
 */
class LogAlertThresholdCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:alert-threshold';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alertar si la cantidad de errores recientes en system_logs supera el umbral configurado';

    /**
     * Cooldown entre alertas para no spamear en cada corrida del scheduler
     * mientras la tasa de errores se mantiene alta — una alerta cada 30 min
     * como máximo, independiente de cada cuánto corra el comando.
     */
    private const COOLDOWN_CACHE_KEY = 'logs:alert-threshold:last-notified';
    private const COOLDOWN_MINUTES = 30;

    public function handle(): void
    {
        $status = 'SUCCESS';

        try {
            $threshold = LogAlertSettings::errorThreshold();
            $windowMinutes = LogAlertSettings::windowMinutes();
            $since = now()->subMinutes($windowMinutes);

            $errorCount = SystemLog::where('level', 'error')
                ->where('created_at', '>=', $since)
                ->count();

            if ($errorCount < $threshold) {
                $output = "Sin alerta: {$errorCount} errores en los últimos {$windowMinutes} min (umbral: {$threshold}).";
            } elseif (Cache::has(self::COOLDOWN_CACHE_KEY)) {
                $output = "Umbral superado ({$errorCount}/{$threshold}) pero se omite: alerta ya enviada en los últimos " . self::COOLDOWN_MINUTES . ' min.';
            } else {
                $this->notify($errorCount, $threshold, $windowMinutes);
                Cache::put(self::COOLDOWN_CACHE_KEY, true, now()->addMinutes(self::COOLDOWN_MINUTES));
                $output = "Alerta enviada: {$errorCount} errores en los últimos {$windowMinutes} min (umbral: {$threshold}).";
            }
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = sprintf(
                'Error al ejecutar la tarea: %s en archivo %s línea %d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            $this->error($output);
        }

        // Envuelto en try/catch: si logCommandExecution() fallara por
        // cualquier motivo, el error visible al operador debe ser el
        // original de la tarea, no uno de logging que lo enmascare.
        try {
            app(CommandService::class)->logCommandExecution($this->signature, $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }

    private function notify(int $errorCount, int $threshold, int $windowMinutes): void
    {
        $message = "Se registraron {$errorCount} errores en los últimos {$windowMinutes} minutos (umbral configurado: {$threshold}). Revisar /logs.";

        // 'critical' se persiste vía el canal 'database' (DatabaseLogHandler)
        // y queda visible en /logs como una entrada más — visibilidad
        // mínima garantizada aunque el correo falle o no esté configurado.
        Log::critical('[logs:alert-threshold] ' . $message);

        $recipient = Setting::active()->byParam('support_email')->value('value');

        if ($recipient) {
            Mail::raw($message, function ($mail) use ($recipient) {
                $mail->to($recipient)->subject('[PlayRKE] Alerta: pico de errores del sistema');
            });
        }
    }
}
