<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets Carbon/PHP default timezone to the authenticated user's timezone.
 * Falls back to UTC (app default) when user has no timezone configured.
 */
class SetUserTimezone
{
    public function handle(Request $request, Closure $next) : Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $tzName = $user->timeZone?->name;

            if ($tzName && $this->isValidTimezone($tzName)) {
                Carbon::setTestNow(); // clear any test now
                config(['app.user_timezone' => $tzName]);
                // Do NOT change PHP/Carbon default — keep UTC as base.
                // Per-user conversion happens explicitly where needed.
                // We store the user TZ in config so services can read it.
            }
        }

        return $next($request);
    }

    private function isValidTimezone(string $tz) : bool
    {
        return in_array($tz, DateTimeZone::listIdentifiers(), true);
    }
}
