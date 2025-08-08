<?php

namespace App\Traits\Helpers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait EncryptTrait
{
    public function generateUniqueCode($model, $size = 14)
    {
        do {
            $code = Str::random($size);
        } while (app("App\\Models\\$model")::where('encrypted', $code)->exists());

        return $code;
    }

    public function valEncrypt()
    {
        return Str::random(10);
    }

    function replaceLettersWithNumbers($text)
    {
        $replacements = [
            'A' => '4',
            'E' => '3',
            'I' => '1',
            'O' => '0',
            'S' => '5',
            'B' => '8',
            'G' => '9',
            'L' => '7',
            'T' => '7',
            'Z' => '2'
        ];

        $convertedText = strtr(strtoupper($text), $replacements);

        return $convertedText;
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

    function generateUniqueId($text, $length = 14)
    {
        // Generar un hash SHA-1 del texto
        $hash = sha1($text);

        // Extraer una subcadena de longitud deseada
        return substr($hash, 0, $length);
    }

    public function getHashMD5($value = '', $num = 7)
    {
        date_default_timezone_set(config('app.timezone'));
        $timestamp = time();

        // Concatenar los valores con una cadena aleatoria para asegurar unicidad
        $randomString = bin2hex(random_bytes($num)); // Genera una cadena aleatoria
        $data = $value . $timestamp . $randomString;

        // Generar un hash MD5 del valor concatenado
        $uniqueId = md5($data);

        return $uniqueId;
    }

    function generateBase64Key($length = 32)
    {
        // Generar bytes aleatorios
        $randomBytes = random_bytes($length);

        // Codificar los bytes en base64
        $base64Key = base64_encode($randomBytes);

        // Retornar la clave en formato Base64
        return $base64Key;
    }

    function generateFormattedString($arr = [])
    {
        // Concatenar las partes con guiones
        $formattedString = $arr[0] . '-' . $arr[1] . '-' . $arr[2] . '-' . $arr[3];

        return $formattedString;
    }

    public function getCodeUUID($model = 'UUIDCodes')
    {
        do {
            $code = strtoupper(Str::uuid()->toString());
        } while (app("App\\Models\\$model")::where('code_uuid', $code)->exists());

        return $code;
    }

    public function handleChangePassword($model, $id)
    {
        DB::beginTransaction();

        try {
            $userID = app("App\\Models\\$model")::getValueById(decrypt($id), 'user');

            if (!$userID) {
                return ['Error' => 'Cliente no posee usuario del sistema'];
            }

            $newPassw = $this->valEncrypt();

            User::where('id', $userID)->update(['password' => Hash::make($newPassw)]);

            DB::commit();
            return ['PWD' => $newPassw];
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return ['Error' => true];
        }
    }

    private function saveImage(UploadedFile $image, $folder, $creatorID)
    {
        try {
            if ($image && $image->isValid()) {
                // Crear un nombre único para el archivo
                $fileName = Carbon::now()->timestamp . '.' . $image->getClientOriginalExtension();

                // Crear una carpeta personalizada usando el ID del creator
                $creatorFolder = "$folder/$creatorID";

                // Eliminar cualquier archivo existente en la carpeta
                Storage::disk('public')->deleteDirectory($creatorFolder);

                // Guardar el nuevo archivo en la carpeta específica
                $image->storeAs($creatorFolder, $fileName, 'public');

                return $fileName;
            }

            throw new \Exception('El archivo no es válido o está corrupto.');
        } catch (\Exception $e) {
            // Registrar el error para depuración
            Log::error("Error al guardar la imagen: " . $e->getMessage());

            throw new HttpResponseException(response()->json([
                'message' => 'Error al guardar la imagen. Inténtalo de nuevo.',
                'error' => $e->getMessage()
            ], 500));
        }
    }
}
