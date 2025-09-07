<?php

namespace App\Http\Controllers;

use App\Models\BanksVenezuela;
use App\Models\BinanceAccounts;
use App\Models\Commerce;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\DocumentType;
use App\Models\PagoMovil;
use App\Models\PaymentMethodsCOL;
use App\Models\Pix;
use App\Repositories\User\Roles\RoleRepository;
use App\Traits\Users\UserValTrait;

class DirectoryController extends Controller
{
    use UserValTrait;

    public function index($countryCode)
    {
        $user = $this->user(['id', 'user_type']);
        $userID = $user->id;
        $userType = RoleRepository::getValueById($user->user_type, 'prefix');

        if ($userType === 'customer') {
            return redirect()->back();
        }

        $countryCode = strtolower($countryCode);
        $validCodes = ['usdt', 'br', 'co', 've'];

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
        $moduleId  = null;
        $additionalData = [];

        switch ($isoCode3) {
            case 'USDT':
                $paymentType = 'Registro';
                $moduleId  = 'binance';
                $additionalData = [
                    'accounts' => BinanceAccounts::where('user', $userID)->get(),
                    'accountTypes' => BinanceAccounts::getAccountTypes()
                ];
                break;

            case 'BRA': // Brasil
                $paymentType = 'Pix';
                $moduleId  = 'pix';
                $additionalData = [
                    'pixs' => Pix::where('user', $userID)->get(),
                    'pixKeys' => Pix::getPixKeys()
                ];
                break;

            case 'COL': // Colombia
                $paymentType = 'Registro';
                $moduleId  = 'payment-method-col';
                $additionalData = [
                    'paymentMethods' => PaymentMethodsCOL::where('user', $userID)->get(),
                    'arrPaymentMethod' => ['B' => 'Bancolombia', 'D' => 'Daviplata', 'N' => 'Nequi']
                ];
                break;

            case 'VEN': // Venezuela
                $paymentType = 'Pago Movil';
                $moduleId  = 'pagomovil';
                $additionalData = [
                    'pagomovils' => PagoMovil::with('Bank')->where('user', $userID)->get(),
                    'documentType' => DocumentType::getDocumentTypeByCountry($country->id),
                    'banks' => BanksVenezuela::all()
                ];
                break;
        }

        if ($userType === 'commerce_id') {
            $commerce = Commerce::getValueByUserId($userID, 'id');

            $commerceCurrencies = CommerceExchangeRate::getCurrenciesByCommerce($commerce);

            $this->checkBankDirectoriesExistence($commerceCurrencies);
        }

        // Determinar la ruta de la vista
        $viewPath = "modules.all.directory.$countryCode.index";

        return view($viewPath, array_merge(
            compact('countries', 'countryCode', 'paymentType', 'moduleId', 'title'),
            $additionalData
        ));
    }
}
