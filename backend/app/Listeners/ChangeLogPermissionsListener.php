<?php

namespace App\Listeners;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;

class ChangeLogPermissionsListener
{
    /**
     * Handle the event.
     */
    public function handle(MessageLogged $event)
    {
        // Define el archivo de log
        $logFile = storage_path('logs/laravel.log');

        // if (file_exists($logFile)) {
        //     try {
        //         // Verificar si el archivo ya tiene los permisos y grupo correctos
        //         $currentGroup = posix_getgrgid(filegroup($logFile))['name'];
        //         $currentPermissions = fileperms($logFile) & 0777;

        //         // Cambiar el grupo solo si es necesario
        //         if ($currentGroup !== 'bingocash') {
        //             chgrp($logFile, 'bingocash');  // Cambiar el grupo al que pertenece el archivo
        //         }

        //         // Cambiar los permisos solo si es necesario
        //         if ($currentPermissions !== 0664) {
        //             chmod($logFile, 0664);         // Establecer permisos rw-rw-r--
        //         }
        //     } catch (\Exception $e) {
        //         // Log de error si ocurre un problema al cambiar permisos
        //         Log::error('Error al cambiar permisos del archivo de log: ' . $e->getMessage());
        //     }
        // }
    }
}
