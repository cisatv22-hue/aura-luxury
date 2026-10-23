<?php

namespace App\Http\Middleware;

use App\Models\Announcement;
use App\Security\TurnstileVerifier;
use App\Settings\StorefrontSettings;
use Illuminate\Http\Request;
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
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'appUrl' => config('app.url'),
            'seoDefaults' => [
                'title' => app(StorefrontSettings::class)->default_seo_title,
                'description' => app(StorefrontSettings::class)->default_seo_description,
            ],
            'storeSettings' => [
                'whatsapp_number' => app(StorefrontSettings::class)->whatsapp_number,
                'instagram_handle' => app(StorefrontSettings::class)->instagram_handle,
                'free_shipping_threshold_cents' => app(StorefrontSettings::class)->free_shipping_threshold_cents,
            ],
            'turnstileSiteKey' => app(TurnstileVerifier::class)->enabled()
                ? config('integrations.turnstile.site_key')
                : null,
            'auth' => [
                'user' => $request->user(),
                'favoriteProductIds' => $request->user()
                    ? $request->user()->customer?->wishlistItems()->pluck('product_id')->map(fn ($id) => (int) $id)->all() ?? []
                    : [],
            ],
            'canManageStore' => $request->user()?->can('catalog.view') ?? false,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'announcement' => Announcement::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->latest('id')
                ->first(['message', 'link_url'])
                ?->only(['message', 'link_url']),
        ];
    }
}
