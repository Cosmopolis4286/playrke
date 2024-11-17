<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class EjecutarPrediccion extends Command
{
    protected $signature = 'prediccion:ejecutar {archivo_csv}';
    protected $description = 'Ejecuta el script Python para hacer predicción de números';

    public function handle()
    {
        // Obtiene el argumento (archivo CSV) que se pasa al comando
        $archivoCsv = $this->argument('archivo_csv');
        
        // Comando para ejecutar el script Python
        $comando = "python " . base_path("storage/app/python/predict_animals.py") . " " . storage_path("app/data/{$archivoCsv}");
        
        // Ejecutar el comando y obtener la salida
        $resultado = shell_exec($comando);
        
        // Mostrar la salida en la consola
        $this->info($resultado);
    }
}
