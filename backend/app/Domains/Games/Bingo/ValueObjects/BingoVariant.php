<?php

namespace App\Domains\Games\Bingo\ValueObjects;

/**
 * Encapsula todas las constantes estructurales de una variante de bingo.
 *
 * Es la única fuente de verdad para:
 *  - Número de bolas en el bombo (totalChips)
 *  - Estructura del cartón (columnas, filas, rangos numéricos, casilla libre)
 *  - Formato del código de ficha (ej. "B-7", "col1-12", "1-45")
 *  - Patrones automáticos predeterminados de la variante
 *
 * Todos los servicios que necesiten datos específicos de la variante
 * deben obtenerlos aquí en lugar de usar constantes hardcoded.
 */
final class BingoVariant
{
    // Identificadores canónicos de variante (guardados en bingo_rooms_config.game_variant)
    public const BINGO_75 = 'BINGO_75';
    public const BINGO_90 = 'BINGO_90';
    public const BINGO_80 = 'BINGO_80';
    public const BINGO_30 = 'BINGO_30';

    public const ALL = [self::BINGO_75, self::BINGO_90, self::BINGO_80, self::BINGO_30];

    /**
     * Definición completa de cada variante.
     *
     * Estructura por columna:
     *   'label'    => etiqueta visual (letra o número)
     *   'min'      => número mínimo del rango
     *   'max'      => número máximo del rango
     *   'count'    => cuántos números se colocan en el cartón por columna
     *   'freeRow'  => índice de fila (base-0) con casilla libre, o null
     *
     * Columnas de 90 bolas: 9 columnas numéricas (0-8).
     * Columnas de 80 bolas: 4 columnas numéricas (0-3).
     * Columnas de 30 bolas: 3 columnas numéricas (0-2).
     *
     * @var array<string, array{
     *   totalChips: int,
     *   rows: int,
     *   columns: array<int, array{label: string, min: int, max: int, count: int, freeRow: int|null}>,
     *   freeCellExists: bool,
     *   defaultPatternBoxes: string,
     *   label: string,
     *   description: string,
     * }>
     */
    private static array $definitions = [];

    private static function definitions(): array
    {
        if (!empty(self::$definitions)) {
            return self::$definitions;
        }

        self::$definitions = [

            // ──────────────────────────────────────────────────────
            // 75 BOLAS — cuadrícula 5×5, letras B I N G O
            // ──────────────────────────────────────────────────────
            self::BINGO_75 => [
                'totalChips'         => 75,
                'rows'               => 5,
                'freeCellExists'     => true,
                'defaultPatternBoxes'=> 'ALL',
                'label'              => 'Bingo 75 bolas',
                'description'        => 'Cuadrícula 5×5, letras B·I·N·G·O, casilla libre central',
                'columns' => [
                    ['label' => 'B', 'min' =>  1, 'max' => 15, 'count' => 5, 'freeRow' => null],
                    ['label' => 'I', 'min' => 16, 'max' => 30, 'count' => 5, 'freeRow' => null],
                    ['label' => 'N', 'min' => 31, 'max' => 45, 'count' => 4, 'freeRow' => 2],  // N[2] libre
                    ['label' => 'G', 'min' => 46, 'max' => 60, 'count' => 5, 'freeRow' => null],
                    ['label' => 'O', 'min' => 61, 'max' => 75, 'count' => 5, 'freeRow' => null],
                ],
            ],

            // ──────────────────────────────────────────────────────
            // 90 BOLAS — cuadrícula 3 filas × 9 columnas
            // Cada fila tiene exactamente 5 números y 4 celdas vacías (null).
            // Columna 0 → 1-9 (solo 9 valores posibles)
            // Columnas 1-8 → 10-19, 20-29, … 80-90
            // ──────────────────────────────────────────────────────
            self::BINGO_90 => [
                'totalChips'         => 90,
                'rows'               => 3,
                'freeCellExists'     => false,
                'defaultPatternBoxes'=> 'ALL',
                'label'              => 'Bingo 90 bolas',
                'description'        => 'Cuadrícula 3×9, 5 números por fila, 4 espacios vacíos por fila',
                'columns' => [
                    // Columna 0: números 1-9  (solo 9 posibles, máx 3 por columna)
                    ['label' => '1',  'min' =>  1, 'max' =>  9, 'count' => 3, 'freeRow' => null],
                    ['label' => '2',  'min' => 10, 'max' => 19, 'count' => 3, 'freeRow' => null],
                    ['label' => '3',  'min' => 20, 'max' => 29, 'count' => 3, 'freeRow' => null],
                    ['label' => '4',  'min' => 30, 'max' => 39, 'count' => 3, 'freeRow' => null],
                    ['label' => '5',  'min' => 40, 'max' => 49, 'count' => 3, 'freeRow' => null],
                    ['label' => '6',  'min' => 50, 'max' => 59, 'count' => 3, 'freeRow' => null],
                    ['label' => '7',  'min' => 60, 'max' => 69, 'count' => 3, 'freeRow' => null],
                    ['label' => '8',  'min' => 70, 'max' => 79, 'count' => 3, 'freeRow' => null],
                    // Columna 8: números 80-90 (11 posibles)
                    ['label' => '9',  'min' => 80, 'max' => 90, 'count' => 3, 'freeRow' => null],
                ],
            ],

            // ──────────────────────────────────────────────────────
            // 80 BOLAS — cuadrícula 4×4 sin casilla libre
            // Columnas 1-4, cada una cubre 20 números
            // ──────────────────────────────────────────────────────
            self::BINGO_80 => [
                'totalChips'         => 80,
                'rows'               => 4,
                'freeCellExists'     => false,
                'defaultPatternBoxes'=> 'ALL',
                'label'              => 'Bingo 80 bolas',
                'description'        => 'Cuadrícula 4×4 sin casilla libre, 4 columnas de 20 números',
                'columns' => [
                    ['label' => '1', 'min' =>  1, 'max' => 20, 'count' => 4, 'freeRow' => null],
                    ['label' => '2', 'min' => 21, 'max' => 40, 'count' => 4, 'freeRow' => null],
                    ['label' => '3', 'min' => 41, 'max' => 60, 'count' => 4, 'freeRow' => null],
                    ['label' => '4', 'min' => 61, 'max' => 80, 'count' => 4, 'freeRow' => null],
                ],
            ],

            // ──────────────────────────────────────────────────────
            // 30 BOLAS (Speed Bingo) — cuadrícula 3×3 sin casilla libre
            // Columnas 1-3, cada una cubre 10 números
            // ──────────────────────────────────────────────────────
            self::BINGO_30 => [
                'totalChips'         => 30,
                'rows'               => 3,
                'freeCellExists'     => false,
                'defaultPatternBoxes'=> 'ALL',
                'label'              => 'Speed Bingo 30 bolas',
                'description'        => 'Cuadrícula 3×3 sin casilla libre, partidas rápidas',
                'columns' => [
                    ['label' => '1', 'min' =>  1, 'max' => 10, 'count' => 3, 'freeRow' => null],
                    ['label' => '2', 'min' => 11, 'max' => 20, 'count' => 3, 'freeRow' => null],
                    ['label' => '3', 'min' => 21, 'max' => 30, 'count' => 3, 'freeRow' => null],
                ],
            ],
        ];

        return self::$definitions;
    }

    // ── Accesores públicos ───────────────────────────────────────

    public static function get(string $variant): array
    {
        return self::definitions()[$variant]
            ?? throw new \InvalidArgumentException("Variante de bingo desconocida: {$variant}");
    }

    public static function totalChips(string $variant): int
    {
        return self::get($variant)['totalChips'];
    }

    public static function rows(string $variant): int
    {
        return self::get($variant)['rows'];
    }

    /** @return array<int, array{label: string, min: int, max: int, count: int, freeRow: int|null}> */
    public static function columns(string $variant): array
    {
        return self::get($variant)['columns'];
    }

    /** Etiquetas de las columnas (ej. ['B','I','N','G','O'] o ['1','2',…,'9']) */
    public static function columnLabels(string $variant): array
    {
        return array_column(self::columns($variant), 'label');
    }

    public static function hasFreeCell(string $variant): bool
    {
        return self::get($variant)['freeCellExists'];
    }

    /** Etiqueta humana (ej. "Bingo 75 bolas") */
    public static function label(string $variant): string
    {
        return self::get($variant)['label'];
    }

    /** Devuelve true si el identificador de variante es válido */
    public static function isValid(string $variant): bool
    {
        return array_key_exists($variant, self::definitions());
    }

    /**
     * Convierte un número sorteado en su código de ficha según la variante.
     * 75 bolas → "B-7", 90/80/30 bolas → "col{n}-{num}" simplificado.
     */
    public static function chipCode(string $variant, int $chip): string
    {
        $columns = self::columns($variant);

        foreach ($columns as $col) {
            if ($chip >= $col['min'] && $chip <= $col['max']) {
                return "{$col['label']}-{$chip}";
            }
        }

        // fallback: no debería ocurrir con datos válidos
        return "?-{$chip}";
    }

    /**
     * Para el 75-ball clásico, devuelve la letra de columna para
     * compatibilidad con el código existente de BingoDrawService.
     * Para otras variantes usa el label numérico de la columna.
     */
    public static function columnForChip(string $variant, int $chip): ?string
    {
        foreach (self::columns($variant) as $col) {
            if ($chip >= $col['min'] && $chip <= $col['max']) {
                return $col['label'];
            }
        }
        return null;
    }

    /**
     * Genera la estructura de card_numbers para el cartón.
     *
     * Para BINGO_75 (5×5 con casilla libre):
     *   { "B": [1,7,null,13,4], … } — null en posición freeRow
     *
     * Para BINGO_90 (3×9 con celdas vacías distribuidas):
     *   { "0": [5,null,null,null,14,null,null,62,null], … }
     *   (array de 3 posiciones con null = celda vacía)
     *
     * Para BINGO_80/30 (sin casilla libre):
     *   { "0": [3,22,41,67], … }
     *
     * Retorna array listo para json_encode.
     */
    public static function generateCardMatrix(string $variant): array
    {
        if ($variant === self::BINGO_90) {
            return self::generateCard90();
        }
        return self::generateCardStandard($variant);
    }

    // ── Generadores internos ─────────────────────────────────────

    /**
     * Genera cartón estándar (75/80/30 bolas).
     * Cada clave es el label de la columna.
     */
    private static function generateCardStandard(string $variant): array
    {
        $matrix = [];

        foreach (self::columns($variant) as $col) {
            $numbers = self::uniqueNumbers($col['min'], $col['max'], $col['count']);
            sort($numbers); // ordenar de menor a mayor dentro de la columna

            if ($col['freeRow'] !== null) {
                // Insertar null en la posición libre (75 bolas: N[2])
                array_splice($numbers, $col['freeRow'], 0, [null]);
            }

            $matrix[$col['label']] = $numbers;
        }

        return $matrix;
    }

    /**
     * Genera cartón de 90 bolas con la distribución auténtica:
     * - 3 filas × 9 columnas
     * - Exactamente 5 números por fila y 4 celdas vacías (null) por fila
     * - Cada columna contiene entre 1 y 3 números (máx 3 por columna en el cartón)
     * - Los números en cada columna aparecen ordenados de menor a mayor
     *
     * Representación en card_numbers:
     *   Clave = label de columna ('1'..'9')
     *   Valor = array de 3 elementos, null = celda vacía
     *   Ejemplo: { "1": [5,null,8], "2": [10,17,null], … }
     */
    private static function generateCard90(): array
    {
        $cols = self::columns(self::BINGO_90);
        $numCols = count($cols); // 9
        $numRows = 3;
        $numbersPerRow = 5;
        $totalNumbers = $numRows * $numbersPerRow; // 15

        // Paso 1: Para cada columna, seleccionar cuántos números tendrá (0-3)
        // con la restricción de que la suma total sea 15 (5 por fila × 3 filas)
        // Usamos un algoritmo de distribución aleatoria válida.
        $colCounts = self::distributeNumbers90($numCols, $numRows, $numbersPerRow);

        // Paso 2: Para cada columna, elegir los números reales
        $selectedByCol = [];
        foreach ($cols as $i => $col) {
            $count = $colCounts[$i];
            if ($count === 0) {
                $selectedByCol[$i] = [];
                continue;
            }
            $nums = self::uniqueNumbers($col['min'], $col['max'], $count);
            sort($nums);
            $selectedByCol[$i] = $nums;
        }

        // Paso 3: Distribuir en filas (3 filas, 5 números por fila)
        // Para cada fila, decidir qué columnas tienen número.
        // Cada columna tiene como máximo $numRows números (uno por fila), así que
        // sus números pendientes deben repartirse en las filas restantes — si una
        // columna tiene tantos números pendientes como filas quedan, es obligatorio
        // colocarla ahora o se quedará sin espacio para todos sus números.
        // Priorizamos por "urgencia" (pendientes / filas restantes) para garantizar
        // que cada fila cierre con exactamente 5 números, sin números huérfanos.
        $grid = array_fill(0, $numRows, array_fill(0, $numCols, null)); // [fila][col] = null|número
        $colPointers = array_fill(0, $numCols, 0); // puntero de siguiente número disponible por col

        for ($row = 0; $row < $numRows; $row++) {
            $rowsLeftIncludingThis = $numRows - $row;

            $available = [];
            foreach ($cols as $i => $col) {
                $pending = count($selectedByCol[$i]) - $colPointers[$i];
                if ($pending > 0) {
                    $available[] = $i;
                }
            }

            // Obligatorias: su cantidad pendiente exige usar TODAS las filas restantes.
            $mandatory = array_values(array_filter($available, function ($i) use ($selectedByCol, $colPointers, $rowsLeftIncludingThis) {
                $pending = count($selectedByCol[$i]) - $colPointers[$i];
                return $pending >= $rowsLeftIncludingThis;
            }));

            $optional = array_values(array_diff($available, $mandatory));
            shuffle($optional);

            $chosenCols = array_slice(
                array_merge($mandatory, $optional),
                0,
                $numbersPerRow,
            );

            foreach ($chosenCols as $ci) {
                $grid[$row][$ci] = $selectedByCol[$ci][$colPointers[$ci]];
                $colPointers[$ci]++;
            }
        }

        // Paso 4: Convertir a formato card_numbers
        // Cada columna = array de 3 elementos (filas), null = vacío
        $matrix = [];
        foreach ($cols as $i => $col) {
            $colArray = [];
            for ($row = 0; $row < $numRows; $row++) {
                $colArray[] = $grid[$row][$i]; // null o número
            }
            $matrix[$col['label']] = $colArray;
        }

        return $matrix;
    }

    /**
     * Distribuye 15 números entre 9 columnas (3 filas × 5 por fila)
     * con restricción: cada columna puede tener 0, 1, 2 o 3 números.
     * Retorna array[9] con el count por columna.
     */
    private static function distributeNumbers90(int $numCols, int $numRows, int $perRow): array
    {
        $total = $numRows * $perRow; // 15
        $maxPerCol = $numRows;       // 3

        // Generar una distribución válida por backtracking aleatorio
        $counts = array_fill(0, $numCols, 0);
        $remaining = $total;
        $colsLeft = $numCols;

        for ($i = 0; $i < $numCols; $i++) {
            $colsLeft--;
            $minForThis = max(0, $remaining - $maxPerCol * $colsLeft);
            $maxForThis = min($maxPerCol, $remaining);
            $counts[$i]  = rand($minForThis, $maxForThis);
            $remaining  -= $counts[$i];
        }

        return $counts;
    }

    /** Genera $count números únicos en el rango [$min, $max] */
    private static function uniqueNumbers(int $min, int $max, int $count): array
    {
        $pool = range($min, $max);
        shuffle($pool);
        return array_slice($pool, 0, $count);
    }

    /**
     * Valida que una matriz de cartón elegida manualmente por el jugador
     * respete exactamente las reglas estructurales de la variante:
     *  - Mismas columnas (labels) que la definición
     *  - Cada columna con la longitud correcta (rows de la variante)
     *  - Números dentro del rango [min, max] de su columna
     *  - Sin duplicados dentro de la misma columna
     *  - Celda libre (freeRow) en null para variantes con freeCellExists
     *  - Para BINGO_90: exactamente 5 números no-null por fila (las demás null)
     *
     * No exige unicidad entre cartones ni entre jugadores — solo estructura interna.
     *
     * @param string $variant
     * @param array<string, array<int, int|null>> $matrix Mismo shape que generateCardMatrix()
     * @param bool $allowMovedFreeSpace Si true (solo sala BINGO_75 con allow_move_free_space),
     *   la casilla libre puede estar en cualquier celda del cartón en vez de la posición fija
     *   (col['freeRow']) — se exige exactamente un único null en todo el cartón. Si false
     *   (default), comportamiento estándar: la libre debe estar exactamente en col['freeRow'].
     * @return string|null Mensaje de error si es inválida, null si es válida
     */
    public static function validateCustomMatrix(string $variant, array $matrix, bool $allowMovedFreeSpace = false): ?string
    {
        $columns = self::columns($variant);
        $rows    = self::rows($variant);

        // array_keys() devuelve int para claves que parecen enteros (p.ej. '1', '2')
        // aunque la matriz se haya construido con labels string — normalizamos a
        // string antes de comparar para no falsear el chequeo de estructura.
        $expectedLabels = array_column($columns, 'label');
        $givenLabels    = array_map('strval', array_keys($matrix));

        if ($expectedLabels !== $givenLabels) {
            return 'La estructura de columnas del cartón no coincide con la variante de la sala.';
        }

        $seenNumbers = [];

        if ($allowMovedFreeSpace) {
            $totalNulls = 0;

            foreach ($columns as $col) {
                $values = $matrix[$col['label']] ?? null;

                if (!is_array($values) || count($values) !== $rows) {
                    return "La columna {$col['label']} debe tener exactamente {$rows} casillas.";
                }

                $nonNullCount = 0;
                $nullsInCol   = 0;

                foreach ($values as $value) {
                    if ($value === null) {
                        $nullsInCol++;
                        $totalNulls++;
                        if ($nullsInCol > 1) {
                            return "La columna {$col['label']} no puede tener más de una casilla libre.";
                        }
                        continue;
                    }

                    if (!is_int($value) || $value < $col['min'] || $value > $col['max']) {
                        return "El número {$value} en la columna {$col['label']} está fuera del rango permitido ({$col['min']}-{$col['max']}).";
                    }

                    if (isset($seenNumbers[$value])) {
                        return "El número {$value} está repetido en el cartón.";
                    }
                    $seenNumbers[$value] = true;

                    $nonNullCount++;
                }

                if ($nonNullCount !== ($rows - $nullsInCol)) {
                    return "La columna {$col['label']} tiene un número faltante.";
                }
            }

            if ($totalNulls !== 1) {
                return 'El cartón debe tener exactamente una casilla libre.';
            }

            return null;
        }

        foreach ($columns as $col) {
            $values = $matrix[$col['label']] ?? null;

            if (!is_array($values) || count($values) !== $rows) {
                return "La columna {$col['label']} debe tener exactamente {$rows} casillas.";
            }

            $nonNullCount = 0;

            foreach ($values as $row => $value) {
                $isFreeRow = $col['freeRow'] !== null && $col['freeRow'] === $row;

                if ($value === null) {
                    if (!$isFreeRow && $variant !== self::BINGO_90) {
                        return "Falta un número en la columna {$col['label']}, fila " . ($row + 1) . '.';
                    }
                    continue;
                }

                if ($isFreeRow) {
                    return "La casilla libre de la columna {$col['label']} debe estar vacía.";
                }

                if (!is_int($value) || $value < $col['min'] || $value > $col['max']) {
                    return "El número {$value} en la columna {$col['label']} está fuera del rango permitido ({$col['min']}-{$col['max']}).";
                }

                if (isset($seenNumbers[$value])) {
                    return "El número {$value} está repetido en el cartón.";
                }
                $seenNumbers[$value] = true;

                $nonNullCount++;
            }

            if ($variant !== self::BINGO_90 && $col['freeRow'] === null && $nonNullCount !== $rows) {
                return "La columna {$col['label']} debe tener {$rows} números.";
            }
        }

        if ($variant === self::BINGO_90) {
            for ($row = 0; $row < $rows; $row++) {
                $filled = 0;
                foreach ($columns as $col) {
                    if (($matrix[$col['label']][$row] ?? null) !== null) {
                        $filled++;
                    }
                }
                if ($filled !== 5) {
                    return 'Cada fila debe tener exactamente 5 números — la fila ' . ($row + 1) . " tiene {$filled}.";
                }
            }
        }

        return null;
    }
}
