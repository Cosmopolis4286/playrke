<?php

namespace App\Traits;

use App\Models\Transactions;
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

    public function generateRandomIV($length = 16)
    {
        return random_bytes($length); // Genera un IV aleatorio de 16 bytes
    }

    public function encryptData($jsonObject, $secretKey)
    {
        $text = json_encode($jsonObject); // Convierte el objeto JSON a una cadena
        $iv = $this->generateRandomIV();
        $key = base64_decode($secretKey); // Convierte la clave base64 a binario
        $cipher = "aes-256-cbc";
        $encryptedText = openssl_encrypt($text, $cipher, $key, 0, $iv);
        $ivHex = bin2hex($iv);
        return $ivHex . ':' . $encryptedText;
    }

    public function decryptData($encryptedText, $secretKey)
    {
        list($ivHex, $encryptedData) = explode(':', $encryptedText);
        $iv = hex2bin($ivHex);
        $key = base64_decode($secretKey); // Convierte la clave base64 a binario
        $cipher = "aes-256-cbc";
        $decryptedText = openssl_decrypt($encryptedData, $cipher, $key, 0, $iv);
        return json_decode($decryptedText, true); // Convierte la cadena JSON a un objeto
    }   
}
