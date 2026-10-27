<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Collection;
use App\Models\HomeSection;
use App\Models\Look;
use App\Models\LookItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\SizeGuide;
use App\Observers\ProductPriceObserver;
use App\Observers\StorefrontDataObserver;
use App\Observers\VariantPriceObserver;
use App\Payments\MockPaymentGateway;
use App\Payments\PaymentGateway;
use App\Shipping\MockShippingProvider;
use App\Shipping\ShippingProvider;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, fn () => match (config('integrations.payments.driver')) {
            'mock' => new MockPaymentGateway,
            default => throw new \InvalidArgumentException('El proveedor de pagos configurado no está disponible.'),
        });
        $this->app->bind(ShippingProvider::class, fn () => match (config('integrations.shipping.driver')) {
            'mock' => new MockShippingProvider,
            default => throw new \InvalidArgumentException('El proveedor de envíos configurado no está disponible.'),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        Product::observe(ProductPriceObserver::class);
        ProductVariant::observe(VariantPriceObserver::class);
        foreach ([
            Product::class,
            ProductColor::class,
            ProductVariant::class,
            Category::class,
            Collection::class,
            Promotion::class,
            PriceHistory::class,
            HomeSection::class,
            Banner::class,
            Look::class,
            LookItem::class,
            SizeGuide::class,
            Order::class,
            OrderItem::class,
        ] as $model) {
            $model::observe(StorefrontDataObserver::class);
        }
        RateLimiter::for('orders', fn (Request $request) => Limit::perMinute(5)
            ->by('order-phone:'.preg_replace('/\D+/', '', (string) $request->input('phone', ''))));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
