<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Policies\System\JobsCenterPolicy;
use App\Rules\CronExpressionRule;
use App\Services\Roles\SuperAdmin\Dashboard\FailedJobService;
use App\Services\System\Jobs\PendingJobsService;
use App\Services\System\Scheduler\ScheduledCommandService;
use Cron\CronExpression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel "Jobs y Scheduler" — vista compuesta de comandos programados
 * (routes/console.php), colas pendientes (Redis) y jobs fallidos
 * (failed_jobs). Solo superadmin (multiPermission:system.jobs en la ruta +
 * JobsCenterPolicy como capa de defensa independiente).
 *
 * FailedJobService se reutiliza tal cual desde
 * app/Services/Roles/SuperAdmin/Dashboard/ (ya usado por el widget "Salud
 * del sistema" del dashboard, vía FailedJobController bajo
 * superadmin/failed-jobs/*) — ninguna lógica de negocio se duplica, este
 * controller solo la expone bajo un prefijo de ruta adicional.
 */
final class JobsCenterController extends Controller
{
    public function __construct(
        private readonly ScheduledCommandService $scheduler,
        private readonly PendingJobsService $pendingJobs,
        private readonly FailedJobService $failedJobs,
    ) {
    }

    public function failedJobs() : JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        return response()->json(['success' => true, 'data' => $this->failedJobs->list()]);
    }

    public function forgetFailedJob(string $uuid) : JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        $forgotten = $this->failedJobs->forget($uuid);

        return response()->json([
            'success' => $forgotten,
            'message' => $forgotten ? 'Job descartado correctamente.' : 'No se pudo descartar el job (puede que ya no exista).',
        ]);
    }

    public function index() : Response
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        return Inertia::render('Jobs/Index', [
            'scheduledCommands' => $this->scheduler->list(),
            'pendingJobs' => $this->pendingJobs->summary(),
            'failedJobs' => $this->failedJobs->list(),
        ]);
    }

    public function pendingJobs() : JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        return response()->json(['success' => true, 'data' => $this->pendingJobs->summary()]);
    }

    public function pendingJobsByQueue(string $queue) : JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        return response()->json(['success' => true, 'data' => $this->pendingJobs->list($queue)]);
    }

    /**
     * Preview en vivo de las próximas ejecuciones para una expresión cron
     * que el usuario aún está escribiendo — permite validar sintaxis y "ver"
     * la cadencia real antes de guardar, sin duplicar la lógica de parseo
     * cron en el frontend (dragonmantank/cron-expression ya es dependencia
     * de Laravel, no hace falta agregar una librería cron nueva en JS).
     */
    public function previewScheduledCommandFrequency(Request $request, string $id) : JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        [, $cronExpression] = $this->validateFrequencyInput($request, $id);

        $cron = new CronExpression($cronExpression);
        $nextRuns = collect(range(0, 4))
            // ISO 8601 (zona del servidor, que es también la del scheduler
            // real) — el frontend lo convierte a hora local del navegador.
            ->map(fn (int $nth) => $cron->getNextRunDate('now', $nth, false)->format('c'))
            ->all();

        return response()->json([
            'success' => true,
            'data' => [
                'frequency' => ScheduledCommandService::describeCron($cronExpression),
                'next_runs' => $nextRuns,
                'server_timezone' => config('app.timezone'),
            ],
        ]);
    }

    public function resetScheduledCommandFrequency(Request $request, string $id) : JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        $this->scheduler->resetFrequency($id, $request->user());

        return response()->json(['success' => true, 'message' => 'Frecuencia restablecida al valor por defecto.']);
    }

    public function retryAllFailedJobs() : JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        $count = $this->failedJobs->retryAll();

        return response()->json(['success' => true, 'message' => "{$count} job(s) reencolado(s) correctamente."]);
    }

    public function retryFailedJob(string $uuid) : JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        $retried = $this->failedJobs->retry($uuid);

        return response()->json([
            'success' => $retried,
            'message' => $retried ? 'Job reencolado correctamente.' : 'No se pudo reencolar el job (puede que ya no exista).',
        ]);
    }

    /**
     * "Ejecutar ahora" — corre inmediatamente el comando ya catalogado en
     * scheduled_commands (no acepta texto libre: $id resuelve a un registro
     * existente en ScheduledCommandService::runNow(), que es quien toma su
     * columna `command` para Artisan::call()).
     */
    public function runScheduledCommand(Request $request, string $id) : JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        $result = $this->scheduler->runNow($id, $request->user());

        return response()->json([
            'success' => $result['exit_code'] === 0,
            'message' => $result['exit_code'] === 0
                ? 'Comando ejecutado correctamente.'
                : "El comando finalizó con código de salida {$result['exit_code']}.",
            'data' => ['output' => $result['output']],
        ]);
    }

    public function scheduledCommandHistory(string $command) : JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        return response()->json(['success' => true, 'data' => $this->scheduler->history($command)]);
    }

    public function scheduledCommands() : JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        return response()->json(['success' => true, 'data' => $this->scheduler->list()]);
    }

    public function toggleScheduledCommand(Request $request, string $id) : JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        $request->validate(['enabled' => 'required|boolean']);

        $updated = $this->scheduler->toggle($id, (bool) $request->boolean('enabled'), $request->user());

        return response()->json([
            'success' => true,
            'message' => $updated->is_enabled ? 'Comando activado correctamente.' : 'Comando desactivado correctamente.',
        ]);
    }

    public function updateScheduledCommandFrequency(Request $request, string $id) : JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        [$scheduled, $cronExpression] = $this->validateFrequencyInput($request, $id);

        $updated = $this->scheduler->updateFrequency($scheduled, $cronExpression, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Frecuencia actualizada correctamente.',
            'data' => ['frequency' => ScheduledCommandService::describeCron($updated->cron_expression)],
        ]);
    }

    /**
     * @return array{0: \App\Models\Commands\ScheduledCommand, 1: string}
     */
    private function validateFrequencyInput(Request $request, string $id) : array
    {
        $scheduled = $this->scheduler->find($id);

        // Validación manual (no FormRequest type-hinted): min_interval_seconds
        // del comando solo se conoce tras cargar $scheduled, y la regla de
        // cadencia mínima de CronExpressionRule lo necesita en construcción —
        // un FormRequest inyectado por type-hint valida antes de que este
        // controller pueda proveer ese contexto.
        $request->validate([
            'cron_expression' => ['required', 'string', 'max:100', new CronExpressionRule($scheduled->min_interval_seconds)],
        ], [
            'cron_expression.required' => 'La expresión cron es obligatoria.',
            'cron_expression.max' => 'La expresión cron no puede exceder 100 caracteres.',
        ]);

        return [$scheduled, trim((string) $request->string('cron_expression'))];
    }
}
