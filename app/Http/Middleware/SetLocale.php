<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED = ['id', 'en', 'ko'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        if ($request->user() && $request->user()->locale) {
            $locale = $request->user()->locale;
        } elseif ($session = $request->session()->get('locale')) {
            $locale = $session;
        } else {
            $locale = config('app.locale');
        }

        if (in_array($locale, static::SUPPORTED, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
