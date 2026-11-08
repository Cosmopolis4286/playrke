<?php

namespace Tests\Unit\Domains\Bingo;

use App\Domains\Games\Bingo\Jobs\StartBingoDrawJob;
use Tests\TestCase;

/**
 * El sorteo debe ir por la conexión dedicada (retry_after = duración de un
 * sorteo) y la cola bingo-draw: con la conexión general (retry_after 90 s) el
 * trabajo de un sorteo en curso volvía a quedar disponible para otro worker.
 */
class StartBingoDrawJobQueueTest extends TestCase
{
    public function test_job_uses_the_dedicated_draw_connection_and_queue() : void
    {
        config(['queue.bingo_draw_connection' => 'bingo-draw']);

        $job = new StartBingoDrawJob('123');

        $this->assertSame('bingo-draw', $job->connection);
        $this->assertSame('bingo-draw', $job->queue);
        $this->assertSame(3600, (int) config('queue.connections.bingo-draw.retry_after'));
    }
}
