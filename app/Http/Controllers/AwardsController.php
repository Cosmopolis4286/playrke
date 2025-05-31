<?php

namespace App\Http\Controllers;

use App\Models\UserType;
use App\Traits\UserVal;

class AwardsController extends Controller
{
    use UserVal;

    public function index()
    {
        $userData = $this->getUserData();

        $creatorType = $userData['type'];
        $creatorID = $userData['id'];

        if (in_array($creatorType, ['seller'])) {
            return redirect()->back();
        }

        $title = 'Premios';
        $name = 'awards';

        $creator = ['type' => $creatorType, 'id' => encrypt($creatorID)];

        $additionalData = [];

        return view('modules.all.awards.index', array_merge(
            compact('title', 'name', 'creator'),
            $additionalData
        ));
    }
}
