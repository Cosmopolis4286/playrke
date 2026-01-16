<?php

namespace App\Services\Currencies;

class CurrencyService
{
    public function __construct() {}

    public function getAvailableCurrencies(int $userId)
    {
        $creatorType = RoleRepository::getValueById(User::getValueById($user, 'user_type'), 'prefix');

        switch ($creatorType) {
            case 'user_id': // Jugador
                $currencies = [User::getValueById($user, 'country')];
                break;
            case 'admin': // Admin
                # code...
                break;
            case 'commerce_id': // Comercio
                $commerce = Commerce::getValueByUserId($user, 'id');
                $currencies = CommerceCurrency::getAvailableCurrencies($commerce);
                break;
            case 'seller': // Vendedor
                $seller = Seller::getValueByUserId($user, 'id');
                $currencies = SellerCurrency::getAvailableCurrencies($seller);
                break;
            case 'customer': // Cliente
                $seller = Seller::getValueByUserId($user, 'id');
                $currencies = SellerCurrency::getAvailableCurrencies($seller);
                break;
            default:
                $currencies = [];
                break;
        }

        return $currencies;
    }
}
