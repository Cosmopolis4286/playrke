<?php

namespace App\Services\RKE;

use App\Models\CommandLog;

class HandleCommands
{
    /**
     * Registra la ejecución del comando en la base de datos.
     *
     * @param string $signature
     * @param string $output
     * @param string $status
     * @return void
     */
    public function logCommandExecution(string $signature, string $output, string $status): void
    {
        CommandLog::create([
            'command' => $signature,
            'output' => $output,
            'status' => $status,
            'executed_at' => now(),
        ]);
    }
}
