<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Games;
use App\Models\User;
use App\Models\UserType;
use App\Traits\Filters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LotteryController extends Controller
{
    use Filters;

    public function index($userToken = false)
    {
        if ($userToken) {
            $userType = 'customer';
            $userToken = true;
            $user = Customer::getValueByToken($userToken, 'user');
        } else {
            $user = Auth::id();
            $userTypeID = User::getValueById($user, 'user_type');
            $userType = UserType::getValueById($userTypeID, 'prefix');
        }

        if (!in_array($userType, ['admin', 'commerce', 'customer'])) {
            return redirect()->back();
        }

        $viewData = [
            'layout' => !$userToken ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Loterias',
            'userToken' => $userToken,
            'name' => 'lottery'
        ];

        $viewData = ['route' => 'modules.game.lottery.index', 'data' => $viewData];

        return $this->handleViewData($viewData, $userType);
    }
}
