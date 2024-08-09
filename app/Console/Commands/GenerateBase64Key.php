<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateBase64Key extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:base64key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new Base64 key';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Generar una nueva clave Base64
        $key = base64_encode(random_bytes(32));

        // Mostrar la clave generada
        $this->info("Generated Base64 Key: " . $key);

        // Opcional: Guardar la clave en el archivo .env
        $path = base_path('.env');
        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                'JSON_ENCRYPTION_KEY=' . env('JSON_ENCRYPTION_KEY'),
                'JSON_ENCRYPTION_KEY=' . $key,
                file_get_contents($path)
            ));
            $this->info("Key saved to .env file");
        }

        return 0;
    }
}
