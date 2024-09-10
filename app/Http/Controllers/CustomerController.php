<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerController extends Controller
{

    public function create(Request $request) {}

    public function exchangeRate()
    {
        $title = 'Tasas de Cambio';
        $name = 'currency';
        $wallet = $this->wallet();
        $commerce = $this->commerce(['id', 'user']);

        $coins = Coin::whereIn('id', $this->getAvailableCoins($commerce->user))
            ->whereDoesntHave('CommerceExchangeRate', function ($query) use ($commerce) {
                $query->where('commerce', $commerce->id);
            })->get(['id', 'symbol', 'long_name']);

        $commerceCoins = CommerceExchangeRate::getCoinsByCommerce($commerce->id, 1);

        $coinsNotDirectories = $this->checkBankDirectoriesExistence($commerce->user, $commerceCoins);

        $this->changeStatusByCoinsWithoutDirectories($coinsNotDirectories);

        $exchangeRate = CommerceExchangeRate::where('commerce', $commerce->id)->get();

        return view('modules.commerce.currency.index', compact('title', 'name', 'coins', 'wallet', 'exchangeRate'));
    }
}
