<?php

namespace App\Traits;

use App\Models\Commerce;
use App\Models\User;
use App\Models\UUIDCodes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait EncryptRKE
{
    public function generateUniqueCode($table, $size = 14)
    {
        do {
            $code = Str::random($size);
        } while (DB::table($table)->where('encrypted', $code)->exists());

        return $code;
    }

    public function encryptDraw($table, $id)
    {
        $existingSymbols = DB::table($table)->where('room', $id)->pluck('sym')->toArray();

        try {
            for ($i = count($existingSymbols) + 1; $i <= 10; $i++) {
                $num = $i;
                if ($i == 10) $num = 0;
                do {
                    $code = Str::random(3);
                } while (in_array($code, $existingSymbols));

                DB::table($table)->insert(['num' => $num, 'sym' => $code, 'room' => $id]);
                $existingSymbols[] = $code;
            }
            return true;
        } catch (\Exception $e) {
            return false;
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

    public function getHashMD5($value = '', $num = 7)
    {
        $timestamp = time();

        // Concatenar los valores con una cadena aleatoria para asegurar unicidad
        $randomString = bin2hex(random_bytes($num)); // Genera una cadena aleatoria
        $data = $value . $timestamp . $randomString;

        // Generar un hash MD5 del valor concatenado
        $uniqueId = md5($data);

        return $uniqueId;
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
            $code = Str::uuid()->toString();
        } while (app("App\\Models\\$model")::where('uuid_code', $code)->exists());

        return $code;
    }

    public function handleChangePassword($userType, $id)
    {
        DB::beginTransaction();

        try {
            $userID = $userType::getValueById(decrypt($id));

            dd($userID);

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
}
