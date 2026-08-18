<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Policies\System\JobsCenterPolicy;
use App\Services\Roles\SuperAdmin\Dashboard\FailedJobService;
use App\Services\System\Jobs\PendingJobsService;
use App\Services\System\Scheduler\ScheduledCommandService;
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
    ) {}

    public function index(): Response
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        return Inertia::render('Jobs/Index', [
            'scheduledCommands' => $this->scheduler->list(),
            'pendingJobs'       => $this->pendingJobs->summary(),
            'failedJobs'        => $this->failedJobs->list(),
        ]);
    }

    public function scheduledCommands(): JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        return response()->json(['success' => true, 'data' => $this->scheduler->list()]);
    }

    public function toggleScheduledCommand(Request $request, string $id): JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        $request->validate(['enabled' => 'required|boolean']);

        $updated = $this->scheduler->toggle($id, (bool) $request->boolean('enabled'), $request->user());

        return response()->json([
            'success' => true,
            'message' => $updated->is_enabled ? 'Comando activado correctamente.' : 'Comando desactivado correctamente.',
        ]);
    }

    public function scheduledCommandHistory(string $command): JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        return response()->json(['success' => true, 'data' => $this->scheduler->history($command)]);
    }

    public function pendingJobs(): JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        return response()->json(['success' => true, 'data' => $this->pendingJobs->summary()]);
    }

    public function pendingJobsByQueue(string $queue): JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        return response()->json(['success' => true, 'data' => $this->pendingJobs->list($queue)]);
    }

    public function failedJobs(): JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        return response()->json(['success' => true, 'data' => $this->failedJobs->list()]);
    }

    public function retryFailedJob(string $uuid): JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        $retried = $this->failedJobs->retry($uuid);

        return response()->json([
            'success' => $retried,
            'message' => $retried ? 'Job reencolado correctamente.' : 'No se pudo reencolar el job (puede que ya no exista).',
        ]);
    }

    public function retryAllFailedJobs(): JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        $count = $this->failedJobs->retryAll();

        return response()->json(['success' => true, 'message' => "{$count} job(s) reencolado(s) correctamente."]);
    }

    public function forgetFailedJob(string $uuid): JsonResponse
    {
        $this->authorize('manage', JobsCenterPolicy::class);

        $forgotten = $this->failedJobs->forget($uuid);

        return response()->json([
            'success' => $forgotten,
            'message' => $forgotten ? 'Job descartado correctamente.' : 'No se pudo descartar el job (puede que ya no exista).',
        ]);
    }
}
