<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

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
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'username' => $request->user()->username,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role->value,
                    'is_active' => $request->user()->is_active,
                ] : null,
            ],
            'flash' => fn (): array => [
                'success' => $request->session()->get('success') ?? $request->session()->get('status'),
                'error' => $request->session()->get('error') ?? $request->session()->get('danger'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info') ?? $request->session()->get('message'),
            ],
            'store' => function (): array {
                $profile = Setting::storeProfile();

                return [
                    'name' => $profile['store_name'],
                    'logo_url' => $profile['store_logo_url'],
                    'favicon_url' => $profile['store_favicon_url'],
                    'primary_color' => $profile['primary_color'],
                ];
            },
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
