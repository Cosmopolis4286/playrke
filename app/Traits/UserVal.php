<?php

namespace App\Traits;

use App\Models\Coin;
use App\Models\Commerce;
use App\Models\CommerceCountriesCoins;
use App\Models\Country;
use App\Models\PagoMovil;
use App\Models\Pix;
use App\Models\ProviderRKE;
use App\Models\Seller;
use App\Models\SellerCountriesCoins;
use App\Models\User;
use App\Models\UserProvider;
use App\Models\UserType;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait UserVal
{
    public function user()
    {
        return Auth::user();
    }

    public function wallet()
    {
        return Wallet::with('Coin')->where("user", Auth::id())->first();
    }

    public function commerce($fields = ['*'])
    {
        return Commerce::select($fields)->where('user', Auth::id())->first();
    }

    public function seller($fields = ['*'])
    {
        return Seller::select($fields)->where('user', Auth::id())->first();
    }

    public function getAvailableCountries($user)
    {
        $userTypeID = User::getValueById($user, 'user_type');
        $userType = UserType::getValueById($userTypeID, 'prefix');

        switch ($userType) {
            case 'user': // Jugador
                $countries = [User::getValueById($user, 'country')];
                break;
            case 'admin': // Admin
                # code...
                break;
            case 'commerce': // Comercio
                $commerce = Commerce::getValueByUserId($user, 'id');
                $countries = CommerceCountriesCoins::getAvailableCountries($commerce);
                break;
            case 'seller': // Vendedor
                $seller = Seller::getValueByUserId($user, 'id');
                $countries = SellerCountriesCoins::getAvailableCountries($seller);
                break;
            default:
                $countries = [];
                break;
        }

        return $countries;
    }

    public function getAvailableCoins($user)
    {
        $userType = UserType::getValueById(User::getValueById($user, 'user_type'), 'prefix');

        switch ($userType) {
            case 'user': // Jugador
                $coins = [User::getValueById($user, 'country')];
                break;
            case 'admin': // Admin
                # code...
                break;
            case 'commerce': // Comercio
                $commerce = Commerce::getValueByUserId($user, 'id');
                $coins = CommerceCountriesCoins::getAvailableCoins($commerce);
                break;
            case 'seller': // Vendedor
                $seller = Seller::getValueByUserId($user, 'id');
                $coins = SellerCountriesCoins::getAvailableCoins($seller);
                break;
            case 'customer': // Cliente
                $seller = Seller::getValueByUserId($user, 'id');
                $coins = SellerCountriesCoins::getAvailableCoins($seller);
                break;
            default:
                $coins = [];
                break;
        }

        return $coins;
    }

    public function checkBankDirectoriesExistence($user, $coins)
    {
        $coinsNotDirectories = [];

        foreach ($coins as $coin) {
            switch ($coin) {
                case 2:
                    // Bs (Venezuela) PagoMovil
                    $pagomovil = PagoMovil::where('user', $user)->exists();
                    if (!$pagomovil) $coinsNotDirectories[] = $coin;
                    break;
                case 3:
                    // R$ (Brasil) Pix
                    $pix = Pix::where('user', $user)->exists();
                    if (!$pix) $coinsNotDirectories[] = $coin;
                    break;
                case 4:
                    // $ (Colombia) Pix
                    $coinsNotDirectories[] = $coin;
                    break;
                default:
                    return response()->json(['error' => 'Tipo de moneda no soportado.'], 400);
            }
        }
        return $coinsNotDirectories;
    }

    public function getPaymentDetailsByUserId($user, $coin)
    {
        try {
            $user = User::getById($user, ['id', 'user_type']);

            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }

            if (!in_array($user->user_type, [2, 3, 5])) {
                return response()->json(['error' => 'Tipo de usuario no válido.'], 400);
            }

            $coinInfo = Coin::getById($coin, ['country', 'symbol']);
            if (!$coinInfo) {
                return response()->json(['error' => 'Moneda no válida.'], 404);
            }

            $isoCode = Country::getCountryValue($coinInfo->country, 'iso_code_2');
            if (!$isoCode) {
                return response()->json(['error' => 'Código ISO del país no encontrado.'], 404);
            }

            $user = $user->id;

            $paymentMethod = [];
            switch ($coin) {
                case 2:
                    // Bs (Venezuela) PagoMovil
                    $pagomovil = PagoMovil::with(['Bank', 'DocumentType'])->where('user', $user)->first();

                    if (!$pagomovil) {
                        return response()->json(['error' => 'El usuario no ha cargado los datos bancarios.'], 404);
                    }

                    $paymentMethod = [
                        '0' => ["({$pagomovil->Bank->code}) {$pagomovil->Bank->name}"],
                        '1' => ["{$pagomovil->DocumentType->value}-{$pagomovil->document}"],
                        '2' => [$pagomovil->phone]
                    ];
                    break;
                case 3:
                    // R$ (Brasil) Pix
                    $pix = Pix::with('ChavePix')->where('user', $user)->first();

                    if (!$pix) {
                        return response()->json(['error' => 'El usuario no ha cargado los datos bancarios.'], 404);
                    }

                    $paymentMethod = ['0' => ["{$pix->ChavePix->value}: {$pix->pix_key_value}"]];
                    break;
                case 4:
                    // $ (Colombia) Pix
                    $pix = Pix::with('ChavePix')->where('user', $user)->first();

                    if (!$pix) {
                        return response()->json(['error' => 'El usuario no ha cargado los datos bancarios.'], 404);
                    }

                    $paymentMethod = ['0' => ["{$pix->ChavePix->value}: {$pix->pix_key_value}"]];
                    break;
                default:
                    return response()->json(['error' => 'Tipo de moneda no soportado.'], 400);
            }

            return [
                'paymentMethod' => $paymentMethod,
                'code' => $isoCode,
                'coin' => $coinInfo->symbol
            ];
        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json(['error' => 'Ha ocurrido un error al procesar la solicitud.'], 500);
        }
    }
}
