<?php

namespace App\Traits\Helpers;

use Exception;
use Illuminate\Support\Str;

/**
 * Trait EncryptTrait
 *
 * Métodos relacionados con encriptación y generación de códigos únicos.
 */
trait EncryptTrait
{
    /**
     * Desencripta un texto encriptado con AES-256-CBC.
     */
    public function decryptData(string $encryptedText, string $secretKey) : ?array
    {
        [$ivHex, $encryptedData] = explode(':', $encryptedText);
        $iv = hex2bin($ivHex);
        $key = base64_decode($secretKey);

        $decryptedText = openssl_decrypt($encryptedData, 'aes-256-cbc', $key, 0, $iv);

        return $decryptedText ? json_decode($decryptedText, true) : null;
    }

    /**
     * Encripta un objeto usando AES-256-CBC.
     *
     * @throws Exception
     */
    public function encryptData(mixed $jsonObject, string $secretKey) : string
    {
        $text = json_encode($jsonObject);
        $iv = random_bytes(16);
        $key = base64_decode($secretKey);

        $encryptedText = openssl_encrypt($text, 'aes-256-cbc', $key, 0, $iv);

        return bin2hex($iv) . ':' . $encryptedText;
    }

    /**
     * Genera un código aleatorio único para un modelo.
     */
    public function generateUniqueCode(string $model, int $size = 14) : string
    {
        do {
            $code = Str::random($size);
        } while (app("App\\Models\\$model")::where('encrypted', $code)->exists());

        return $code;
    }

    /**
     * Genera un UUID único para un modelo.
     */
    public function getCodeUUID(string $model = 'UniqueCode') : string
    {
        do {
            $code = strtoupper(Str::uuid()->toString());
        } while (app("App\\Models\\$model")::where('uuid_code', $code)->exists());

        return $code;
    }

    /**
     * Genera un valor aleatorio (10 caracteres por defecto).
     */
    public function valEncrypt() : string
    {
        return Str::random(10);
    }
}
