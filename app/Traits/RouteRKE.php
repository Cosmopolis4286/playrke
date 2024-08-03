<?php

namespace App\Traits;

trait RouteRKE
{
    public function getRouteRKE()
    {
        if (app()->environment('production')) {
            $rout = 'https://www.playrke.com/';
        }
        $rout = 'http://localhost/play10/public/';

        return $rout;
    }
}
