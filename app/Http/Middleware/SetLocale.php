<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class SetLocale
{
    public function handle(Request $request, Closure $next, ?string $guard = null)
    {
        $isApi = $guard === 'api';
        $locale = Locale::resolve($request, $isApi);

        app()->setLocale($locale);

        // An explicit ?locale choice on a web page is remembered for later visits.
        if (!$isApi && Locale::normalize($request->query('locale')) && $request->cookie(Locale::COOKIE) !== $locale) {
            Cookie::queue(Locale::COOKIE, $locale, 60 * 24 * 365, '/', null, null, false, false, 'Lax');
        }

        return $next($request);
    }
}
