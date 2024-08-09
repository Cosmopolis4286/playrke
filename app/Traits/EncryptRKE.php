<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait EncryptRKE
{
    public function roomEncrypt($table)
    {
        do {
            $code = Str::random(12);
        } while (DB::table($table)->where('encrypted', $code)->exists());

        return $code;
    }
    public function encryptSort($table, $id)
    {
        $existingSymbols = DB::table($table)->where('room', $id)->pluck('sym')->toArray();

        for ($i = count($existingSymbols) + 1; $i <= 10; $i++) {
            $num = $i;
            if ($i == 10) $num = 0;
            do {
                $code = Str::random(3);
            } while (in_array($code, $existingSymbols));

            DB::table($table)->insert(['num' => $num, 'sym' => $code, 'room' => $id]);
            $existingSymbols[] = $code;
        }
    }

    public function valEncrypt()
    {
        return Str::random(10);
    }

    function encryptData($data) {
        // Obtén la clave de cifrado del archivo .env
        $key = env('JSON_ENCRYPTION_KEY');
    
        // Genera un IV (vector de inicialización) aleatorio
        $iv = random_bytes(16); // AES-256-CBC requiere un IV de 16 bytes
    
        // Convierte el array en JSON
        $jsonData = json_encode($data);
    
        // Cifra los datos JSON usando AES-256-CBC
        $ciphertext = openssl_encrypt($jsonData, 'AES-256-CBC', $key, 0, $iv);
    
        // Codifica el IV y el texto cifrado en base64 para almacenarlos juntos
        $encoded = base64_encode($iv . $ciphertext);
    
        return $encoded;
    }

    function decryptData($encryptedData) {
        // Obtén la clave de cifrado del archivo .env
        $key = env('JSON_ENCRYPTION_KEY');
        
        // Decodifica el texto cifrado en base64
        $decoded = base64_decode($encryptedData);
        
        // Extrae el IV (los primeros 16 bytes)
        $iv = substr($decoded, 0, 16);
        
        // Extrae el texto cifrado (el resto del contenido)
        $ciphertext = substr($decoded, 16);
        
        // Descifra el texto cifrado usando AES-256-CBC
        $jsonData = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, 0, $iv);
        
        // Convierte los datos JSON de nuevo a un array
        $data = json_decode($jsonData, true);
        
        return $data;
    }
}
