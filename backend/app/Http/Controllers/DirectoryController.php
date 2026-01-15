<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BankVenezuela;
use App\Models\BinanceAccounts;
use App\Models\Commerce;
use App\Models\Pivots\ExchangeRate\CommerceExchangeRate;
use App\Models\Country;
use App\Models\DocumentType;
use App\Models\Directories\Fiat\Venezuela\PagoMovil;
use App\Models\Directories\Fiat\Colombia\PaymentAccount;
use App\Models\Pix;
use App\Traits\Users\UserTrait;

class DirectoryController extends Controller
{
    use UserTrait;


    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(
        //protected CommerceRepositoryInterface $commerces,
    ) {}

    public function index($countryCode)
    {
        $user = $this->user(['id', 'user_type']);
        $userId = $user->id;
        $userRole = 22;

        if ($userRole === 'customer') {
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

        $countries = Country::getCountriesById($this->getAvailableCountries($userId));

        $paymentType = null;
        $moduleId  = null;
        $additionalData = [];

        switch ($isoCode3) {
            case 'USDT':
                $paymentType = 'Registro';
                $moduleId  = 'binance';
                $additionalData = [
                    'accounts' => BinanceAccounts::where('user', $userId)->get(),
                    'accountTypes' => BinanceAccounts::getAccountTypes()
                ];
                break;

            case 'BRA': // Brasil
                $paymentType = 'Pix';
                $moduleId  = 'pix';
                $additionalData = [
                    'pixs' => Pix::where('user', $userId)->get(),
                    'pixKeys' => Pix::getPixKey()
                ];
                break;

            case 'COL': // Colombia
                $paymentType = 'Registro';
                $moduleId  = 'payment_accounts_co';
                $additionalData = [
                    'paymentMethods' => PaymentAccount::where('user', $userId)->get(),
                    'arrPaymentMethod' => ['B' => 'Bancolombia', 'D' => 'Daviplata', 'N' => 'Nequi']
                ];
                break;

            case 'VEN': // Venezuela
                $paymentType = 'Pago Movil';
                $moduleId  = 'pagomovil';
                $additionalData = [
                    'pagomovils' => PagoMovil::with('Bank')->where('user', $userId)->get(),
                    'documentType' => DocumentType::getDocumentTypeByCountry($country->id),
                    'banks' => BankVenezuela::all()
                ];
                break;
        }

        if ($userRole === 'commerce_id') {
            $commerce = Commerce::getValueByUserId($userId, 'id');

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
