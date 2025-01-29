<?php

namespace App\Http\Controllers;

use App\Models\Banks_VEN;
use App\Models\Commerce;
use App\Models\CommerceCountriesCoins;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\DocumentType;
use App\Models\PagoMovil;
use App\Models\PaymentMethodsCOL;
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
        $userID = $user->id;
        $userType = UserType::getValueById($user->user_type, 'prefix');

        if ($userType === 'customer') {
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
        $countries = Country::getCountriesById($this->getAvailableCountries($userID));

        $paymentType = null;
        $name = null;
        $additionalData = [];

        switch ($country->iso_code_3) {
            case 'BRA': // Brasil
                $paymentType = 'Pix';
                $name = 'Pix';
                $additionalData = [
                    'pixs' => Pix::with('ChavePix')->where('user', $userID)->get(),
                    'pixKeys' => PixKeys::all()
                ];
                break;

            case 'COL': // Colombia
                $paymentType = 'Registro';
                $name = 'payment-method-col';
                $additionalData = [
                    'paymentMethods' => PaymentMethodsCOL::where('user', $userID)->get(),
                    'arrPaymentMethod' => ['B' => 'Bancolombia', 'D' => 'Daviplata', 'N' => 'Nequi']
                ];
                break;

            case 'VEN': // Venezuela
                $paymentType = 'Pago Movil';
                $name = 'PagoMovil';
                $additionalData = [
                    'pagomovils' => PagoMovil::with('Bank')->where('user', $userID)->get(),
                    'documentType' => DocumentType::getDocumentTypeByCountry($country->id),
                    'banks' => Banks_VEN::all()
                ];
                break;
        }

        if (isset($coinNotPayment)) {

            switch ($userType) {
                case 'admin':
                    # code...
                    break;
                case 'commerce':
                    $commerce = Commerce::getValueByUserId($userID, 'id');

                    $coins = CommerceCountriesCoins::getAvailableCoins($commerce);

                    $country = CommerceCountriesCoins::where('commerce', $commerce)
                        ->whereNot('country', $coinNotPayment)->value('country');

                    $countryCode = strtolower(Country::getValue($country, 'iso_code_2'));

                    return redirect()->route('directory.index', ['countryCode' => $countryCode]);
                    break;
                case 'seller':
                    # code...
                    break;

                default:
                    # code...
                    break;
            }
        }

        if ($userType === 'commerce') {
            $commerce = Commerce::getValueByUserId($userID, 'id');

            $commerceCoins = CommerceExchangeRate::getCoinsByCommerce($commerce);

            $this->checkBankDirectoriesExistence('commerce', $commerceCoins);
        }

        // Determinar la ruta de la vista
        $viewPath = "modules.all.directory.$countryCode.index";

        return view($viewPath, array_merge(
            compact('title', 'countryCode', 'countries', 'paymentType', 'name'),
            $additionalData
        ));
    }
}
