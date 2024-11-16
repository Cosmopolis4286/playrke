<?php

namespace App\Http\Controllers;

use App\Traits\UserVal;
use App\Traits\EncryptRKE;
use App\Http\Controllers\Controller;
use App\Traits\Filters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class PredictionController extends Controller
{

    public function createJson()
    {
        $data = [
            "fecha" => "2024-11-16",
            "numeros_del_dia" => [12, 5, 23, 8, 34, 1],
            "historial" => [
                ["fecha" => "2024-11-15", "numeros" => [3, 14, 22, 7, 17, 9]],
                ["fecha" => "2024-11-14", "numeros" => [18, 25, 33, 5, 11, 29]],
                ["fecha" => "2024-11-13", "numeros" => [2, 6, 19, 8, 21, 35]],
                ["fecha" => "2024-11-12", "numeros" => [9, 13, 24, 30, 32, 28]]
            ]
        ];

        // Guardamos el archivo JSON en la carpeta storage/app/python
        Storage::disk('local')->put('python/datos.json', json_encode($data));

        return response()->json(['message' => 'Archivo JSON creado correctamente']);
    }

    public function generatePrediction()
    {
        $pythonScript = storage_path('app/python/predict.py');
        $historyFile = storage_path('app/python/numbers_history.json');
        $outputFile = storage_path('app/python/predictions.json');

        // Ejecutar el script de Python
        $command = "python3 $pythonScript $historyFile $outputFile";
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error ejecutando el script de predicción',
            ], 500);
        }

        // Leer las predicciones generadas
        $predictions = json_decode(file_get_contents($outputFile), true);

        return response()->json([
            'status' => 'success',
            'predictions' => $predictions,
        ]);
    }
}
