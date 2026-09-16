<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $activeCodes = Language::query()->where('is_active', true)->pluck('code');

        $sessionLocale = $request->session()->get('locale');

        $locale = $activeCodes->contains($sessionLocale)
            ? $sessionLocale
            : (Language::query()->where('is_default', true)->value('code') ?? config('app.locale'));

        app()->setLocale($locale);

        return $next($request);
    }
}
