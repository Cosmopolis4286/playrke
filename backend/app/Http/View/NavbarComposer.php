<?php

namespace App\Http\View;

use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NavbarComposer
{
    public function compose(View $view): void
    {
        if (! Auth::check()) {
            $view->with(['navWallet' => null, 'navUser' => null]);
            return;
        }

        $wallet = Wallet::with('currency')
            ->where('user_id', Auth::id())
            ->where('status', 'ACTIVE')
            ->first();

        $view->with([
            'navWallet' => $wallet,
            'navUser'   => Auth::user(),
        ]);
    }
}
