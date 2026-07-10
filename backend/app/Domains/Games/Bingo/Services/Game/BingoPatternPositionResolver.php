<?php

namespace App\Domains\Games\Bingo\Services\Game;

use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;

/**
 * Convierte las posiciones de un patrón de bingo (boxes) en los índices [col, row]
 * del array card_numbers del cartón para una variante dada.
 *
 * Compartido por BingoDrawService (sorteo automático) y BingoWinnerDetectionService
 * (verificación manual "Bingo") para que ambos evalúen exactamente el mismo patrón.
 *
 * Para BINGO_75 (5×5, letras B/I/N/G/O):
 *   boxes = 'B1;I2;N3;G4;O5' — letra+fila (base-1). Casilla libre: N3 → N[2].
 *
 * Para BINGO_90/80/30 (numérico):
 *   Todas las celdas del cartón (ALL) o formato 'col{n};row{m}' futuro.
 *   Por ahora solo se soporta ALL para variantes no-75.
 */
final class BingoPatternPositionResolver
{
    /** Cache de sub-patrones MULTIPLE por num_id — evita N+1 queries durante el sorteo. */
    private array $subPatternCache = [];

    /**
     * @return array<int, array{col: string, row: int, free: bool}>
     */
    public function resolve(BingoPattern $pattern, string $variant = BingoVariant::BINGO_75, array $seen = []): array
    {
        $colDefs  = BingoVariant::columns($variant);
        $labels   = array_column($colDefs, 'label');
        $numRows  = BingoVariant::rows($variant);
        $resolved = [];

        $boxes = $pattern->boxes ?? '';

        // FULL HOUSE: todas las celdas del cartón según la variante
        if (strtoupper(trim($boxes)) === 'ALL') {
            foreach ($colDefs as $col) {
                for ($row = 0; $row < $numRows; $row++) {
                    $isFree = ($col['freeRow'] !== null && $row === $col['freeRow']);
                    $resolved[] = ['col' => $col['label'], 'row' => $row, 'free' => $isFree];
                }
            }
            return $resolved;
        }

        // Patrón MULTIPLE: boxes contiene num_ids de sub-patrones.
        // $seen previene recursión infinita si dos patrones se referencian mutuamente.
        if ($pattern->type_pattern === 'MULTIPLE') {
            $numIds = array_filter(
                array_map('intval', (array) $pattern->boxes_array),
                fn($id) => $id >= 1
            );

            // Precargar todos los sub-patrones en una sola query si no están en cache
            $missing = array_diff($numIds, array_keys($this->subPatternCache));
            if (!empty($missing)) {
                $fetched = BingoPattern::whereIn('num_id', $missing)
                    ->whereNull('user_id')
                    ->get()
                    ->keyBy('num_id');
                foreach ($missing as $id) {
                    $this->subPatternCache[$id] = $fetched->get($id);
                }
            }

            foreach ($numIds as $numId) {
                if (in_array($numId, $seen, true)) {
                    continue; // Evitar recursión circular
                }
                $subPattern = $this->subPatternCache[$numId] ?? null;
                if ($subPattern) {
                    $resolved = array_merge(
                        $resolved,
                        $this->resolve($subPattern, $variant, [...$seen, $numId])
                    );
                }
            }
            return $resolved;
        }

        // Formato 'B1;I2;N3;G4;O5' (75 bolas) o genérico 'col;row' (base-1)
        foreach ($pattern->boxes_array as $box) {
            $box = strtoupper(trim((string) $box));
            if (strlen($box) < 2) continue;

            // Detectar si la primera parte es una letra de columna o un índice numérico
            // Ej: 'B1', 'N3' para 75-ball; posibles extensiones futuras para otras variantes
            $col    = $box[0];
            $rowNum = (int) substr($box, 1); // fila base-1

            if (!in_array($col, $labels, true) || $rowNum < 1 || $rowNum > $numRows) continue;

            $rowIndex = $rowNum - 1;

            // Determinar si es casilla libre (buscar en colDefs)
            $isFree = false;
            foreach ($colDefs as $colDef) {
                if ($colDef['label'] === $col && $colDef['freeRow'] !== null && $colDef['freeRow'] === $rowIndex) {
                    $isFree = true;
                    break;
                }
            }

            $resolved[] = ['col' => $col, 'row' => $rowIndex, 'free' => $isFree];
        }

        return $resolved;
    }
}
