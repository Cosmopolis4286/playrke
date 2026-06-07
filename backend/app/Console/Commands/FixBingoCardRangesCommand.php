<?php

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use Illuminate\Console\Command;

/**
 * Corrige cartones de bingo cuya columna contiene números fuera del rango estándar:
 *   B=1-15, I=16-30, N=31-45, G=46-60, O=61-75
 *
 * Regenera únicamente los números inválidos manteniendo el serial, hash y demás datos.
 */
class FixBingoCardRangesCommand extends Command
{
    protected $signature   = 'bingo:fix-card-ranges {--dry-run : Mostrar cambios sin aplicarlos} {--room= : Limitar a una sala específica (ID)}';
    protected $description = 'Corrige números de cartones de bingo que están fuera del rango de su columna';

    private const COL_RANGES = [
        'B' => [1,  15],
        'I' => [16, 30],
        'N' => [31, 45],
        'G' => [46, 60],
        'O' => [61, 75],
    ];

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $roomId   = $this->option('room');

        $this->info($isDryRun ? '[DRY-RUN] Sin cambios en BD' : 'Iniciando corrección de rangos...');

        $query = BingoCard::query();
        if ($roomId) {
            $query->where('room_id', $roomId);
        }

        $fixed = 0;
        $total = 0;

        $query->chunk(200, function ($cards) use (&$fixed, &$total, $isDryRun) {
            foreach ($cards as $card) {
                $total++;
                $matrix  = $card->card_numbers ?? [];
                $changed = false;

                foreach (self::COL_RANGES as $col => [$lo, $hi]) {
                    $colNums = $matrix[$col] ?? [];
                    $isN     = ($col === 'N');
                    // N tiene 5 slots pero posición [2] puede ser null (celda libre)
                    $newNums = [];

                    // Números ya usados en otras columnas (rangos no solapan, pero por seguridad)
                    $otherCols = $this->alreadyUsed($matrix, $col);

                    foreach ($colNums as $idx => $n) {
                        if ($isN && $idx === 2 && $n === null) {
                            $newNums[] = null;
                            continue;
                        }
                        if ($n === null || $n < $lo || $n > $hi) {
                            // Número inválido: regenerar con exclusión de ya-asignados en esta columna
                            $exclude   = array_merge(array_filter($newNums, fn($x) => $x !== null), $otherCols);
                            $newNums[] = $this->freshNumber($lo, $hi, $exclude);
                            $changed   = true;
                        } else {
                            $newNums[] = $n;
                        }
                    }

                    $matrix[$col] = $newNums;
                }

                if (!$changed) continue;

                $fixed++;
                $this->line("  Card #{$card->id} — corregida");

                if (!$isDryRun) {
                    $card->card_numbers = $matrix;
                    $card->save();
                }
            }
        });

        $this->info("Revisados: {$total} | Corregidos: {$fixed}" . ($isDryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }

    private function alreadyUsed(array $matrix, string $skipCol): array
    {
        $used = [];
        foreach ($matrix as $col => $nums) {
            if ($col === $skipCol) continue;
            foreach ($nums as $n) {
                if ($n !== null) $used[] = $n;
            }
        }
        return $used;
    }

    private function freshNumber(int $lo, int $hi, array $exclude): int
    {
        $pool = array_diff(range($lo, $hi), $exclude);
        if (empty($pool)) {
            // Fallback: devolver un número del rango (no debería ocurrir con rangos no solapados)
            return rand($lo, $hi);
        }
        return $pool[array_rand($pool)];
    }
}
