<?php

namespace App\Traits;

trait RouteRKE
{
    public function getRouteRKE()
    {
        if (app()->environment('production')) {
            $rout = 'https://www.playrke.com/';
        }
        $rout = 'http://localhost/rke-11/public/';

        return $rout;
    }
}
