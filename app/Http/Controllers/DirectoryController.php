<?php

namespace App\Http\Controllers;

use App\Models\Banks_VEN;
use App\Models\Country;
use App\Models\DocumentType;
use App\Models\PagoMovil;
use App\Models\Pix;
use App\Models\PixKeys;
use App\Models\UserType;
use App\Traits\UserVal;

class DirectoryController extends Controller
{
    use UserVal;

    public function index($countryCode)
    {
        $user = $this->user(['id', 'user_type']);

        if (UserType::getValueById($user->user_type, 'prefix') === 'customer') {
            return redirect()->back();
        }

        $countryCode = strtolower($countryCode);
        $validCodes = ['br', 'co', 've'];

        // Redirigir si el código del país no es válido
        if (!in_array($countryCode, $validCodes)) {
            return redirect()->back();
        }

        $country = Country::getCountryByIsoCode($countryCode);

        if (!$country) return redirect()->back();

        $title = "Directorio $country->name";
        $wallet = $this->wallet();
        $countries = Country::getCountriesById($this->getAvailableCountries($user->id));

        $paymentType = null;
        $name = null;
        $additionalData = [];

        switch ($country->iso_code_3) {
            case 'BRA': // Brasil
                $paymentType = 'Pix';
                $name = 'Pix';
                $additionalData = [
                    'pixs' => Pix::with('ChavePix')->where('user', $user->id)->get(),
                    'pixKeys' => PixKeys::all()
                ];
                break;

            case 'COL': // Colombia
                return redirect()->back(); // Se redirige para Colombia

            case 'VEN': // Venezuela
                $paymentType = 'Pago Movil';
                $name = 'PagoMovil';
                $additionalData = [
                    'pagomovils' => PagoMovil::with('Bank')->where('user', $user->id)->get(),
                    'documentType' => DocumentType::getDocumentTypeByCountry($country->id),
                    'banks' => Banks_VEN::all()
                ];
                break;
        }

        // Determinar la ruta de la vista
        $viewPath = "modules.all.directory.$countryCode.index";

        return view($viewPath, array_merge(
            compact('title', 'countryCode', 'countries', 'paymentType', 'name', 'wallet'),
            $additionalData
        ));
    }
}
