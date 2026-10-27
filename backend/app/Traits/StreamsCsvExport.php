<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportación CSV en streaming vía cursor() de Eloquent — evita materializar
 * el resultado completo en memoria. Mismo patrón repetido antes en cada
 * método *Export() de BingoReportController y en AuditController::export().
 */
trait StreamsCsvExport
{
    /**
     * @param string                 $filename Nombre del archivo descargado (sin extensión .csv).
     * @param array<string>          $headers  cabeceras de columnas, en orden
     * @param iterable               $rows     filas de datos ya iterables (típicamente un cursor())
     * @param callable(mixed): array $mapRow   convierte cada fila en un array de valores en el mismo orden que $headers
     */
    protected function streamCsvExport(string $filename, array $headers, iterable $rows, callable $mapRow) : StreamedResponse
    {
        return response()->streamDownload(function() use ($headers, $rows, $mapRow) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_values($mapRow($row)));
            }

            fclose($handle);
        }, $filename . '-' . now()->format('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
