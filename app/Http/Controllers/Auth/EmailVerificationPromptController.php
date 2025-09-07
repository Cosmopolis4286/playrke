<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Currencies\Currency;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {

            $isoCode = Country::getValue(Auth::user()->country, 'iso_code_2');
            $currency = encrypt(Currency::getValueByCountry(Auth::user()->country, 'id'));

            // Crear la variable de sesión 'countryCode'
            $request->session()->put('countryCode', $isoCode);

            // Crear la variable de sesión 'currency'
            $request->session()->put('currency', $currency);

            return redirect()->intended(route('dashboard', absolute: false));
        } else {
            return view('auth.verify-email');
        }
    }
}
