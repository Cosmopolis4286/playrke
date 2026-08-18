<?php

namespace App\Services\System\Jobs;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

/**
 * Jobs pendientes (no fallidos) en las colas Redis del proyecto — distingue
 * "trabajo esperando" de "trabajo que ya falló" (failed_jobs, gestionado por
 * FailedJobService). Un job puede quedar atascado sin nunca fallar hasta
 * agotar reintentos, así que esta señal no es redundante con failedJobsCount.
 *
 * Solo aplica si QUEUE_CONNECTION=redis (confirmado como el driver real de
 * este proyecto en Docker) — con otro driver, size()/list() devuelven vacío.
 */
final class PendingJobsService
{
    /**
     * Colas conocidas del proyecto — se muestran siempre en el resumen
     * aunque estén vacías, para que la UI tenga una tarjeta fija por cola.
     */
    private const KNOWN_QUEUES = ['default', 'bingo-draw', 'bingo-refunds'];

    /**
     * @return array<int, array{queue: string, pending: int}>
     */
    public function summary(): array
    {
        if (config('queue.default') !== 'redis') {
            return array_map(fn (string $queue) => ['queue' => $queue, 'pending' => 0], self::KNOWN_QUEUES);
        }

        return array_map(
            fn (string $queue) => ['queue' => $queue, 'pending' => $this->sizeOf($queue)],
            self::KNOWN_QUEUES
        );
    }

    /**
     * @return array<int, array{job_name: string, attempts: int, available_at: string}>
     */
    public function list(string $queue, int $limit = 50): array
    {
        if (config('queue.default') !== 'redis') {
            return [];
        }

        $connectionName = config('queue.connections.redis.connection', 'default');
        $prefix         = config('database.redis.options.prefix', '');
        $key            = "{$prefix}queues:{$queue}";

        return collect(Redis::connection($connectionName)->lrange($key, 0, $limit - 1))
            ->map(function (string $raw) {
                $decoded = json_decode($raw, true);

                return [
                    'job_name'     => $decoded['displayName'] ?? $decoded['job'] ?? 'Job desconocido',
                    'attempts'     => $decoded['attempts'] ?? 0,
                    'available_at' => isset($decoded['data']['delay']) ? (string) $decoded['data']['delay'] : '—',
                ];
            })
            ->all();
    }

    /**
     * size() del driver Redis suma pendientes + delayed + reserved en un
     * único script Lua atómico (Illuminate\Queue\RedisQueue::size()) — más
     * fiable que un LLEN manual, que solo contaría la lista "ready".
     */
    private function sizeOf(string $queue): int
    {
        try {
            return (int) Queue::connection('redis')->size($queue);
        } catch (\Throwable) {
            return 0;
        }
    }
}
