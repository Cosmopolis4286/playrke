<?php

/**
 * Script para corregir namespaces y use de Repositories automáticamente
 * Ajustado para que los namespaces empiecen por la carpeta principal (ej: Countries)
 * y los contracts mantengan el 'Contracts' en el use.
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Filesystem\Filesystem;

$filesystem = new Filesystem;
$baseDir = realpath(__DIR__ . '/../app/Repositories');
$baseNamespace = 'App\\Repositories';

// Escanea todos los Contracts y genera un map: nombre => namespace
$contractMap = [];
$contractsDir = $baseDir;

foreach ($filesystem->allFiles($contractsDir) as $file) {
    $pathParts = explode(DIRECTORY_SEPARATOR, str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file->getPathname()));
    $filename = array_pop($pathParts);
    $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

    if (in_array('Contracts', $pathParts)) {
        // Mantener "Contracts" solo en el use
        $contractMap[$filenameWithoutExt] = $baseNamespace . '\\' . implode('\\', $pathParts) . '\\' . $filenameWithoutExt;
    }
}

// Escanea todos los Repositories
foreach ($filesystem->allFiles($baseDir) as $file) {
    $contents = $filesystem->get($file->getPathname());

    // 1. Corrige el namespace de la clase (sin Contracts)
    $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
    $relativePath = str_replace(['/', '\\'], '\\', $relativePath);
    $pathParts = explode('\\', $relativePath);
    array_pop($pathParts); // elimina el archivo
    $pathParts = array_filter($pathParts, fn ($p) => $p !== 'Contracts'); // quitar Contracts
    $namespace = $baseNamespace . '\\' . implode('\\', $pathParts);
    $namespace = trim($namespace, '\\');

    if (preg_match('/^namespace\s+.*;/m', $contents)) {
        $contents = preg_replace('/^namespace\s+.*;/m', "namespace {$namespace};", $contents);
    } else {
        $contents = "namespace {$namespace};\n\n" . $contents;
    }

    // 2. Corrige los use de Contracts
    foreach ($contractMap as $contractName => $fullNamespace) {
        $pattern = '/use\s+.*' . preg_quote($contractName, '/') . '\s*;/';
        if (preg_match($pattern, $contents)) {
            $contents = preg_replace($pattern, "use {$fullNamespace};", $contents);
        }
    }

    $filesystem->put($file->getPathname(), $contents);
    echo 'Procesado: ' . $file->getFilename() . PHP_EOL;
}

echo "Namespaces y use de Repositories actualizados correctamente.\n";
