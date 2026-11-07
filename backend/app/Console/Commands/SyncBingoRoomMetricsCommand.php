<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\Metrics\BingoRoomMetricsSynchronizer;
use Illuminate\Console\Command;

final class SyncBingoRoomMetricsCommand extends Command
{
    protected $signature = 'bingo:sync-room-metrics {--room= : Reconciliar una sala específica}';

    protected $description = 'Recalcula contadores y métricas de salas Bingo desde lotes y participaciones persistidas';

    public function handle(BingoRoomMetricsSynchronizer $synchronizer) : int
    {
        $roomId = $this->option('room');
        if ($roomId) {
            $result = $synchronizer->synchronize((string) $roomId);
            $this->line($this->formatResult((string) $roomId, $result));

            return self::SUCCESS;
        }

        $processed = 0;
        $withLegacyLots = 0;

        BingoRoom::query()->select('id')->orderBy('id')->chunkById(100, function($rooms) use ($synchronizer, &$processed, &$withLegacyLots) {
            foreach ($rooms as $room) {
                $result = $synchronizer->synchronize((string) $room->id);
                $processed++;
                if (! $result['financials_reconciled']) {
                    $withLegacyLots++;
                }
                $this->line($this->formatResult((string) $room->id, $result));
            }
        });

        $this->info("Salas procesadas: {$processed}; con lotes legacy que requieren conciliación: {$withLegacyLots}.");

        return self::SUCCESS;
    }

    private function formatResult(string $roomId, array $result) : string
    {
        $status = $result['financials_reconciled'] ? 'finanzas reconciliadas' : 'finanzas preservadas (lotes legacy)';
        return "Sala {$roomId}: jugadores={$result['total_players']}, cartones={$result['total_cards']}, {$status}.";
    }
}