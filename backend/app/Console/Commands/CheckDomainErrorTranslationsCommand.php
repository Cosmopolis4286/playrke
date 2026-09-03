<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Verifica que todo ERROR_CODE lanzado por una clase DomainException tenga
 * traducción en TODOS los idiomas soportados (lang/{locale}/errors.php).
 *
 * Nace del hallazgo de auditoría: dominios completos (Bingo, AdminSubsidiary)
 * quedaron sin ninguna clave de traducción durante meses sin que nada lo
 * detectara — el usuario simplemente veía el código crudo en pantalla
 * (Handler::resolveMessage() cae a `?: $errorCode` en silencio). Este
 * comando existe para que esa desincronización falle el build en vez de
 * llegar a producción.
 *
 * Uso: `php artisan check:domain-error-translations` — sale con código 1 y
 * lista los códigos faltantes por idioma si encuentra algo. Pensado para
 * correr en CI junto al resto de checks estáticos.
 *
 * Heurística de extracción: escanea el código fuente de cada clase que
 * extiende DomainException buscando `new self('CODE'` / `new static('CODE'`
 * — no usa reflection en runtime porque casi todos los factory methods
 * requieren argumentos (montos, IDs) imposibles de invocar genéricamente.
 * Solo cuenta como "error code real" un string en MAYÚSCULAS_CON_GUION_BAJO
 * (sin espacios) — varias clases pasan texto humano directo como primer
 * argumento (ej. "No puedes eliminar..."), que no necesita traducción
 * porque YA es el mensaje final (Handler::resolveMessage() cae a
 * `?: $errorCode`, mostrando ese texto tal cual).
 */
class CheckDomainErrorTranslationsCommand extends Command
{
    protected $signature = 'check:domain-error-translations
        {--locales=es,en : Idiomas a verificar, separados por coma}';

    protected $description = 'Verifica que todo ERROR_CODE de dominio tenga traducción en todos los idiomas soportados';

    public function handle(): int
    {
        $locales = array_filter(array_map('trim', explode(',', (string) $this->option('locales'))));

        $errorCodes = $this->extractErrorCodesFromDomainExceptions();

        if (empty($errorCodes)) {
            $this->warn('No se encontró ningún ERROR_CODE — revisa que las clases DomainException existan.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Encontrados %d error-codes en %d clases de dominio.', count($errorCodes), count(array_unique(array_column($errorCodes, 'class')))));

        $hasMissing = false;

        foreach ($locales as $locale) {
            $translations = $this->loadTranslations($locale);
            $missing = [];

            foreach ($errorCodes as $entry) {
                if (!array_key_exists($entry['code'], $translations)) {
                    $missing[] = $entry;
                }
            }

            if (empty($missing)) {
                $this->info("✔ {$locale}: todos los error-codes tienen traducción.");
                continue;
            }

            $hasMissing = true;
            $this->error(sprintf('✘ %s: %d error-code(s) sin traducción:', $locale, count($missing)));

            foreach ($missing as $entry) {
                $this->line("   {$entry['code']}  ({$entry['class']}::{$entry['method']}())");
            }
        }

        return $hasMissing ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, array{code: string, class: string, method: string}>
     */
    private function extractErrorCodesFromDomainExceptions(): array
    {
        $files = File::allFiles(app_path());
        $results = [];

        foreach ($files as $file) {
            $contents = file_get_contents($file->getPathname());

            if ($contents === false || !str_contains($contents, 'extends DomainException')) {
                continue;
            }

            if (!preg_match('/^namespace\s+([^;]+);/m', $contents, $nsMatch)) {
                continue;
            }
            if (!preg_match('/class\s+(\w+)\s+extends\s+DomainException/', $contents, $classMatch)) {
                continue;
            }

            $className = $nsMatch[1] . '\\' . $classMatch[1];

            // Captura cada `public static function NOMBRE(...)` junto con el
            // cuerpo hasta el siguiente `public static function` (o el final
            // de la clase) para poder atribuir cada `new self/static(...)`
            // encontrado dentro a SU método — necesario para el mensaje de
            // error legible ("Clase::metodo()"), no solo para deduplicar.
            preg_match_all(
                '/public static function (\w+)\([^)]*\)\s*:\s*\w+\s*\{(.*?)(?=\n    public static function|\n\})/s',
                $contents,
                $methodMatches,
                PREG_SET_ORDER
            );

            foreach ($methodMatches as $method) {
                [, $methodName, $body] = $method;

                if (!preg_match("/new (?:self|static)\(\s*['\"]([A-Z][A-Z0-9_]*)['\"]/", $body, $codeMatch)) {
                    // Sin match = o no hay `new self(...)` en este método, o
                    // su primer argumento es texto humano (no ERROR_CODE) —
                    // ambos casos correctos de ignorar silenciosamente.
                    continue;
                }

                $results[] = [
                    'code'   => $codeMatch[1],
                    'class'  => $className,
                    'method' => $methodName,
                ];
            }
        }

        // Dedupe por código — varias clases comparten el mismo ERROR_CODE
        // (ej. BINGO_ROOM_NOT_FOUND en BingoRoomException/BingoPatternException).
        $seen = [];
        return array_values(array_filter($results, function ($entry) use (&$seen) {
            if (isset($seen[$entry['code']])) {
                return false;
            }
            $seen[$entry['code']] = true;
            return true;
        }));
    }

    /**
     * @return array<string, string>
     */
    private function loadTranslations(string $locale): array
    {
        $path = base_path("lang/{$locale}/errors.php");

        if (!File::exists($path)) {
            $this->warn("No existe lang/{$locale}/errors.php — se trata como vacío.");
            return [];
        }

        return require $path;
    }
}
