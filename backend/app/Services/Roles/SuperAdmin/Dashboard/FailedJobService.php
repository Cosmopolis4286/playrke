<?php

namespace App\Services\Roles\SuperAdmin\Dashboard;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Gestión de jobs fallidos (tabla failed_jobs) desde el widget "Salud del
 * sistema" del dashboard superadmin — antes solo se mostraba el conteo, sin
 * forma de ver el detalle ni reintentar/descartar desde la UI.
 *
 * Reintento/descarte delegan en los comandos nativos de Laravel
 * (queue:retry, queue:forget) en vez de reimplementar la lógica de
 * reencolado: son el mecanismo oficial y ya manejan payload/conexión/cola
 * originales correctamente.
 */
final class FailedJobService
{
    public function count() : int
    {
        return DB::table('failed_jobs')->count();
    }

    public function forget(string $uuid) : bool
    {
        Artisan::call('queue:forget', ['id' => $uuid]);

        return DB::table('failed_jobs')->where('uuid', $uuid)->doesntExist();
    }

    /**
     * @return array<int, array{uuid: string, connection: string, queue: string, exception_summary: string, failed_at: string, server_timezone: string}>
     */
    public function list(int $limit = 50) : array
    {
        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'uuid' => $row->uuid,
                'connection' => $row->connection,
                'queue' => $row->queue,
                'job_name' => $this->extractJobName($row->payload),
                'exception_summary' => $this->summarizeException($row->exception),
                // failed_jobs.failed_at se inserta vía Date::now() (Carbon) en
                // el propio proceso PHP del worker — ya está en la zona
                // horaria de la app (config('app.timezone')), no en la de la
                // sesión MySQL (que puede ser distinta, ej. SYSTEM/UTC acá).
                // Se parsea explícitamente en esa zona antes de exportar ISO
                // para que el frontend la convierta a hora local del navegador.
                'failed_at' => Carbon::parse($row->failed_at, config('app.timezone'))->toIso8601String(),
                'server_timezone' => config('app.timezone'),
            ])
            ->all();
    }

    public function retry(string $uuid) : bool
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);

        return DB::table('failed_jobs')->where('uuid', $uuid)->doesntExist();
    }

    public function retryAll() : int
    {
        $count = $this->count();
        Artisan::call('queue:retry', ['id' => ['all']]);

        return $count;
    }

    private function extractJobName(string $payload) : string
    {
        $decoded = json_decode($payload, true);

        return $decoded['displayName'] ?? $decoded['job'] ?? 'Job desconocido';
    }

    /**
     * Primera línea de la excepción — el stack trace completo queda
     * disponible vía el propio comando artisan si se necesita, no tiene
     * sentido exponerlo entero en el widget del dashboard.
     */
    private function summarizeException(string $exception) : string
    {
        $firstLine = strtok($exception, "\n") ?: $exception;

        return mb_strimwidth($firstLine, 0, 200, '…');
    }
}
