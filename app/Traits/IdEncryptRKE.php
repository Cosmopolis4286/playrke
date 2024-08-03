<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait IdEncryptRKE
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
}
