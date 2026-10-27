<?php

namespace App\Http\Controllers\Roles\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\Roles\SuperAdmin\Dashboard\FailedJobService;
use Illuminate\Http\JsonResponse;

/**
 * Gestión de jobs fallidos desde el widget "Salud del sistema" del
 * dashboard superadmin. Solo superadmin (ver routes/modules/superadmin.php).
 */
final class FailedJobController extends Controller
{
    public function __construct(private readonly FailedJobService $service)
    {
    }

    public function forget(string $uuid) : JsonResponse
    {
        $forgotten = $this->service->forget($uuid);

        return response()->json([
            'success' => $forgotten,
            'message' => $forgotten
                ? 'Job descartado correctamente.'
                : 'No se pudo descartar el job (puede que ya no exista).',
        ]);
    }

    public function index() : JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->list(),
        ]);
    }

    public function retry(string $uuid) : JsonResponse
    {
        $retried = $this->service->retry($uuid);

        return response()->json([
            'success' => $retried,
            'message' => $retried
                ? 'Job reencolado correctamente.'
                : 'No se pudo reencolar el job (puede que ya no exista).',
        ]);
    }

    public function retryAll() : JsonResponse
    {
        $count = $this->service->retryAll();

        return response()->json([
            'success' => true,
            'message' => "{$count} job(s) reencolado(s) correctamente.",
        ]);
    }
}
