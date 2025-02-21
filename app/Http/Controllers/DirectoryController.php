<?php

namespace App\Http\Controllers;

use App\Models\Banks_VEN;
use App\Models\BinanceAccounts;
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

        if ($countryCode === 'usdt') {
            $title = "Directorio Binance";
            $isoCode3 = 'USDT';
        } else {
            $country = Country::getCountryByIsoCode($countryCode);
            if (!$country) return redirect()->back();

            $title = "Directorio $country->name";
            $isoCode3 = $country->iso_code_3;
        }

        $countries = Country::getCountriesById($this->getAvailableCountries($userID));

        $paymentType = null;
        $name = null;
        $additionalData = [];

        switch ($isoCode3) {
            case 'USDT':
                $paymentType = 'Registro';
                $name = 'payment-method-usdt';
                $additionalData = [
                    'accounts' => BinanceAccounts::where('user', $userID)->get(),
                    'accountTypes' => BinanceAccounts::getAccountTypes()
                ];
                break;

            case 'BRA': // Brasil
                $paymentType = 'Pix';
                $name = 'Pix';
                $additionalData = [
                    'pixs' => Pix::where('user', $userID)->get(),
                    'pixKeys' => Pix::getPixKeys()
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
