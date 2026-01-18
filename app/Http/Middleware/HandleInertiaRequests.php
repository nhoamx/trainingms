<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth.user' => fn () => $request->user()
                ? array_merge($request->user()->only('id', 'name', 'email', 'organization_id'), [
                    'roles' => $request->user()->roles,
                ])
                : null,
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'message' => $request->session()->get('message'),
                'bulk_errors' => $request->session()->get('bulk_errors'),
            ],
            'csrf_token' => fn () => csrf_token(),
            'currentOrganization' => fn () => $request->user() && $request->user()->organization ? $request->user()->organization : null,
            'locale' => fn () => App::getLocale(),
            'translations' => fn () => $this->getTranslations(),
        ]);
    }

    /**
     * Load translations for the current locale.
     *
     * @return array<string, string>
     */
    protected function getTranslations(): array
    {
        $locale = App::getLocale();
        $path = lang_path("{$locale}.json");

        if (File::exists($path)) {
            return json_decode(File::get($path), true) ?? [];
        }

        return [];
    }
}
