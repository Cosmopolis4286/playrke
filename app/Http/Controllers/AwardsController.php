<?php

namespace App\Http\Controllers;

use App\Models\UserType;
use App\Traits\UserVal;

class AwardsController extends Controller
{
    use UserVal;

    public function index()
    {
        $user = $this->user(['id', 'user_type']);
        $userID = $user->id;
        $userType = UserType::getValueById($user->user_type, 'prefix');

        if (in_array($userType, ['seller'])) {
            return redirect()->back();
        }

        $title = 'Premios';
        $name = 'awards';

        $additionalData = [];

        return view('modules.all.awards.index', array_merge(
            compact('title', 'name'),
            $additionalData
        ));
    }
}
