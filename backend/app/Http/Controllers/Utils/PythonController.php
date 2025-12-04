<?php

namespace App\Http\Controllers\Utils;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PythonController extends Controller
{
    public function runPythonScript()
    {
        // Ruta del script Python (asegúrate de que esté correcta)
        $scriptPath = storage_path('app/python/predict.py');
        $jsonInput = storage_path('app/python/numbers_history.json');
        $jsonOutput = storage_path('app/python/predictions.json');

        // Comando para ejecutar el script Python
        $process = new Process(["python", $scriptPath, $jsonInput, $jsonOutput]);

        // Ejecutar el proceso
        try {
            $process->mustRun();  // Ejecuta el proceso y lanza una excepción si algo falla
            return response()->json(['message' => 'El script se ejecutó correctamente']);
        } catch (ProcessFailedException $exception) {
            return response()->json(['error' => 'Error al ejecutar el script: ' . $exception->getMessage()]);
        }
    }
}
