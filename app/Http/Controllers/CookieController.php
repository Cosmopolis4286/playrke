<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class CookieController extends Controller
{
    /**
     * Retorna el valor de la cookie 'referral'
     */
    public function getReferral()
    {
        return response()->json([
            'referral' => Cookie::get('referral')
        ]);
    }
}
