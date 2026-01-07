<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales for the application.
     *
     * @var array<string>
     */
    protected array $supportedLocales = ['es', 'en'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }

    /**
     * Determine the locale to use for this request.
     */
    protected function determineLocale(Request $request): string
    {
        // Priority: 1) Query param, 2) Session, 3) Default to Spanish
        if ($request->has('lang') && in_array($request->query('lang'), $this->supportedLocales)) {
            return $request->query('lang');
        }

        if (Session::has('locale') && in_array(Session::get('locale'), $this->supportedLocales)) {
            return Session::get('locale');
        }

        // Default to Spanish
        return 'es';
    }
}
