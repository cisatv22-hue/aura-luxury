<?php

use App\Actions\ConfirmOrder;
use App\Actions\PlaceOrder;
use App\Actions\UpdateOrderStatus;
use App\Models\AbandonedCart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use App\Notifications\AbandonedCartReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makePhaseNineVariant(int $stock = 5): ProductVariant
{
    $category = Category::create(['name' => 'Playeras', 'slug' => 'playeras-phase9']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Playera AURA',
        'slug' => 'playera-aura-phase9',
        'description' => '<p>Algodón peinado</p>',
        'status' => Product::STATUS_ACTIVE,
        'published_at' => now()->subMinute(),
        'base_price_cents' => 80_000,
    ]);
    $color = ProductColor::create(['product_id' => $product->id, 'name' => 'Negro']);

    return ProductVariant::create([
        'product_id' => $product->id,
        'product_color_id' => $color->id,
        'size' => 'M',
        'sku' => 'AURA-PHASE9-M',
        'stock' => $stock,
        'is_active' => true,
    ]);
}

function phaseNineOrderData(ProductVariant $variant, ?string $coupon = null, ?string $email = null): array
{
    return [
        'name' => 'Cliente de prueba',
        'phone' => '5512345678',
        'email' => $email,
        'city' => 'Puebla',
        'delivery_method' => 'shipping',
        'delivery_postal_code' => '72000',
        'coupon_code' => $coupon,
        'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ];
}

it('applies a valid coupon using integer cents and tracks its redemption', function () {
    $variant = makePhaseNineVariant();
    $coupon = Coupon::query()->create([
        'code' => 'AURA10', 'type' => 'percent', 'value' => 1000,
        'minimum_subtotal_cents' => 50_000, 'usage_limit' => 1, 'is_active' => true,
    ]);

    $order = app(PlaceOrder::class)->handle(phaseNineOrderData($variant, 'aura10'));

    expect($order->subtotal_cents)->toBe(80_000)
        ->and($order->discount_cents)->toBe(8_000)
        ->and($order->total_cents)->toBe(81_900)
        ->and($coupon->fresh()->usage_count)->toBe(1);
});

it('rejects expired, exhausted, and below-minimum coupons without creating an order', function () {
    $variant = makePhaseNineVariant();
    foreach ([
        ['code' => 'EXPIRED', 'ends_at' => now()->subDay(), 'minimum_subtotal_cents' => 0, 'usage_limit' => null, 'usage_count' => 0],
        ['code' => 'LIMITED', 'ends_at' => null, 'minimum_subtotal_cents' => 0, 'usage_limit' => 1, 'usage_count' => 1],
        ['code' => 'MINIMUM', 'ends_at' => null, 'minimum_subtotal_cents' => 100_000, 'usage_limit' => null, 'usage_count' => 0],
    ] as $couponData) {
        Coupon::query()->create([
            ...$couponData,
            'type' => 'amount', 'value' => 1000, 'is_active' => true,
        ]);
        $this->post(route('orders.store'), phaseNineOrderData($variant, $couponData['code']))
            ->assertSessionHasErrors('coupon_code');
    }

    expect(Order::query()->count())->toBe(0);
});

it('only accepts reviews after delivery and keeps them hidden until approved', function () {
    $variant = makePhaseNineVariant();
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);
    $order = app(PlaceOrder::class)->handle(phaseNineOrderData($variant, null, $user->email));
    $confirmed = app(ConfirmOrder::class)->handle($order);
    $shipped = app(UpdateOrderStatus::class)->handle($confirmed, Order::STATUS_SHIPPED);

    $this->post(route('account.reviews.store', ['product' => $variant->product_id]), ['rating' => 5, 'title' => 'Excelente'])
        ->assertSessionHasErrors('body');

    app(UpdateOrderStatus::class)->handle($shipped, Order::STATUS_DELIVERED);
    $this->post(route('account.reviews.store', ['product' => $variant->product_id]), ['rating' => 5, 'title' => 'Excelente', 'body' => 'Muy buena calidad.'])
        ->assertRedirect();

    $review = Review::query()->firstOrFail();
    expect($review->status)->toBe('pending');
    $this->get(route('products.show', ['product' => 'playera-aura-phase9']))->assertDontSee('Excelente');
    $review->update(['status' => 'approved']);
    $this->get(route('products.show', ['product' => 'playera-aura-phase9']))->assertSee('Excelente');
});

it('publishes variant feeds from active catalog data', function () {
    makePhaseNineVariant();

    $this->get(route('feeds.google'))->assertOk()->assertSee('AURA-PHASE9-M')->assertSee('800.00 MXN');
    $this->get(route('feeds.meta'))->assertOk()->assertSee('AURA-PHASE9-M')->assertSee('in stock');
});

it('stores abandoned-cart reminders only after consent and sends them once after 24 hours', function () {
    Notification::fake();
    $variant = makePhaseNineVariant();
    $this->post(route('abandoned-carts.store'), [
        'email' => 'customer@example.com', 'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ])->assertSessionHasErrors('consent');
    expect(AbandonedCart::query()->count())->toBe(0);

    $this->post(route('abandoned-carts.store'), [
        'email' => 'Customer@example.com', 'consent' => true,
        'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ])->assertSessionHas('cart_reminder_saved', true);
    $cart = AbandonedCart::query()->firstOrFail();
    $cart->forceFill(['consented_at' => now()->subHours(25), 'created_at' => now()->subHours(25)])->save();

    $this->artisan('aura:send-cart-reminders')->assertSuccessful();
    Notification::assertSentOnDemand(AbandonedCartReminder::class);
    expect($cart->fresh()->reminder_sent_at)->not->toBeNull();

    $this->get(URL::temporarySignedRoute('abandoned-carts.unsubscribe', now()->addDay(), ['cart' => $cart->id]))->assertRedirect(route('home'));
    expect($cart->fresh()->email)->toBe('')
        ->and($cart->fresh()->items)->toBe([])
        ->and($cart->fresh()->unsubscribed_at)->not->toBeNull();
});
