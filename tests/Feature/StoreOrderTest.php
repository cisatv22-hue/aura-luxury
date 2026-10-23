<?php

use App\Actions\AdjustStock;
use App\Actions\CancelOrder;
use App\Actions\ConfirmOrder;
use App\Actions\PlaceOrder;
use App\Actions\RecordMockRefund;
use App\Actions\SimulateMockPayment;
use App\Actions\UpdateOrderStatus;
use App\Http\Middleware\RequireConfirmedTwoFactor;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\RestockAlert;
use App\Models\Subscriber;
use App\Models\User;
use App\Notifications\NewOrderReceived;
use App\Notifications\RestockAvailable;
use App\Notifications\StaleOrderReminder;
use App\Payments\PaymentGateway;
use App\Payments\VerifiedPaymentEvent;
use App\Settings\StorefrontSettings;
use App\Shipping\ShippingProvider;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('rate-limits orders by normalized phone across client IPs', function () {
    for ($attempt = 0; $attempt < 6; $attempt++) {
        $phone = $attempt % 2 === 0 ? '55 1234 5678' : '5512345678';
        $ip = $attempt % 2 === 0 ? '203.0.113.10' : '203.0.113.11';
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson(route('orders.store'), ['phone' => $phone]);

        if ($attempt < 5) {
            $response->assertUnprocessable();
        } else {
            $response->assertTooManyRequests();
        }
    }
});

it('reminds staff once about new orders older than 48 hours', function () {
    Notification::fake();
    $settings = app(StorefrontSettings::class);
    $settings->orders_notification_email = 'orders@example.com';
    $settings->save();
    $variant = makeAvailableVariant();
    $orderData = [
        'name' => 'Cliente de prueba', 'phone' => '5512345678', 'email' => null, 'city' => 'Puebla',
        'delivery_method' => 'pickup', 'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ];

    $stale = app(PlaceOrder::class)->handle($orderData);
    $stale->forceFill(['created_at' => now()->subHours(49)])->save();
    $recent = app(PlaceOrder::class)->handle($orderData);
    $recent->forceFill(['created_at' => now()->subHours(47)])->save();
    $confirmed = app(PlaceOrder::class)->handle($orderData);
    $confirmed->forceFill(['status' => Order::STATUS_CONFIRMED, 'created_at' => now()->subHours(72)])->save();

    $this->artisan('aura:remind-stale-orders')->assertSuccessful()->expectsOutput('Recordatorios enviados: 1');
    expect($stale->fresh()->stale_reminder_sent_at)->not->toBeNull()
        ->and($recent->fresh()->stale_reminder_sent_at)->toBeNull()
        ->and($confirmed->fresh()->stale_reminder_sent_at)->toBeNull();
    Notification::assertSentOnDemandTimes(StaleOrderReminder::class, 1);

    $this->artisan('aura:remind-stale-orders')->assertSuccessful()->expectsOutput('Recordatorios enviados: 0');
    Notification::assertSentOnDemandTimes(StaleOrderReminder::class, 1);
});

function makeAvailableVariant(int $price = 150_000, int $stock = 5): ProductVariant
{
    $category = Category::create(['name' => 'Sudaderas', 'slug' => 'sudaderas-test']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Sudadera AURA',
        'slug' => 'sudadera-aura-test',
        'description' => 'Prenda de prueba',
        'status' => Product::STATUS_ACTIVE,
        'published_at' => now()->subMinute(),
        'base_price_cents' => $price,
    ]);
    $color = ProductColor::create(['product_id' => $product->id, 'name' => 'Negro']);

    return ProductVariant::create([
        'product_id' => $product->id,
        'product_color_id' => $color->id,
        'size' => 'M',
        'sku' => 'AURA-TEST-M',
        'stock' => $stock,
        'is_active' => true,
    ]);
}

it('queues a new-order email for the configured store recipient', function () {
    Notification::fake();
    $settings = app(StorefrontSettings::class);
    $settings->orders_notification_email = 'orders@example.com';
    $settings->save();
    $variant = makeAvailableVariant();

    $this->post(route('orders.store'), [
        'name' => 'Cliente de prueba',
        'phone' => '5512345678',
        'city' => 'Ciudad de México',
        'delivery_method' => 'pickup',
        'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ])->assertRedirect();

    Notification::assertSentOnDemand(NewOrderReceived::class);
});

it('requires server-verified Turnstile tokens when credentials are configured', function () {
    config(['integrations.turnstile.site_key' => 'site-test', 'integrations.turnstile.secret_key' => 'secret-test']);
    Http::fakeSequence('https://challenges.cloudflare.com/turnstile/v0/siteverify')
        ->push(['success' => true])
        ->push(['success' => false]);
    $variant = makeAvailableVariant();
    $data = [
        'name' => 'Cliente de prueba', 'phone' => '5512345678', 'email' => null, 'city' => 'Puebla',
        'delivery_method' => 'pickup', 'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ];

    $this->post(route('orders.store'), [...$data, 'cf-turnstile-response' => 'token-ok'])->assertRedirect();
    $this->post(route('orders.store'), [...$data, 'cf-turnstile-response' => 'token-falso'])->assertSessionHasErrors('cf-turnstile-response');
    expect(Order::query()->count())->toBe(1);
    Http::assertSentCount(2);
});

it('stores a WhatsApp order using server prices without reserving inventory', function () {
    $variant = makeAvailableVariant();

    $response = $this->post(route('orders.store'), [
        'name' => 'Cliente de prueba',
        'phone' => '55 1234 5678',
        'email' => 'cliente@example.com',
        'city' => 'Ciudad de México',
        'delivery_method' => 'shipping',
        'delivery_postal_code' => '06000',
        'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ]);

    $order = Order::query()->with('customer')->firstOrFail();
    $response->assertRedirect(route('orders.show', ['order' => $order->number], absolute: false));
    $this->assertSame(150_000, $order->subtotal_cents);
    $this->assertSame(0, $order->shipping_cents);
    $this->assertTrue($order->shipping_free);
    $this->assertFalse($order->shipping_needs_confirmation);
    $this->assertSame('525512345678', $order->customer->phone);
    $this->assertSame('06000', $order->delivery_postal_code);
    $this->assertSame(5, $variant->fresh()->stock);
    $this->assertNotEmpty(session('whatsapp_url'));
});

it('keeps mock payment pending and does not accept public webhook data as proof of payment', function () {
    $variant = makeAvailableVariant();
    $order = app(PlaceOrder::class)->handle([
        'name' => 'Cliente de prueba', 'phone' => '5512345678', 'email' => null, 'city' => 'Puebla',
        'delivery_method' => 'shipping', 'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ]);
    app(ConfirmOrder::class)->handle($order);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $order->customer()->update(['user_id' => $user->id]);

    $this->actingAs($user)->post(route('orders.payment.checkout', ['order' => $order->number]))
        ->assertRedirect(route('orders.show', ['order' => $order->number], absolute: true).'?payment=mock');
    $this->actingAs($user)->post(route('orders.payment.checkout', ['order' => $order->number]))
        ->assertRedirect(route('orders.show', ['order' => $order->number], absolute: true).'?payment=mock');
    expect($order->payments()->count())->toBe(1);
    expect(fn () => app(UpdateOrderStatus::class)->handle($order->fresh(), Order::STATUS_PAID))
        ->toThrow(ValidationException::class);
    $this->postJson(route('payments.webhook'), ['event' => 'payment.approved', 'status' => 'paid'])
        ->assertAccepted();

    expect($order->fresh()->status)->toBe(Order::STATUS_CONFIRMED)
        ->and($order->payments()->firstOrFail()->status)->toBe('pending');
});

it('simulates mock payments locally and caps recorded refunds at the paid amount', function () {
    $variant = makeAvailableVariant();
    $order = app(PlaceOrder::class)->handle([
        'name' => 'Cliente de prueba', 'phone' => '5512345678', 'email' => null, 'city' => 'Puebla',
        'delivery_method' => 'shipping', 'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ]);
    $confirmed = app(ConfirmOrder::class)->handle($order);
    $confirmed->update(['channel' => 'online']);
    $payment = $confirmed->payments()->create([
        'provider' => 'mock',
        'provider_reference' => 'mock_checkout_test',
        'status' => 'pending',
        'amount_cents' => $confirmed->total_cents,
    ]);

    $paidOrder = app(SimulateMockPayment::class)->handle($confirmed);
    expect($paidOrder->status)->toBe(Order::STATUS_PAID)
        ->and($payment->fresh()->status)->toBe('paid')
        ->and($payment->fresh()->payload)->toBe(['mock' => true, 'simulated_locally' => true]);

    app(RecordMockRefund::class)->handle($paidOrder, 3000, 'Reembolso parcial de prueba');
    app(RecordMockRefund::class)->handle($paidOrder, $confirmed->total_cents - 3000, 'Completar reembolso mock');

    expect((int) $payment->refunds()->sum('amount_cents'))->toBe((int) $confirmed->total_cents)
        ->and($payment->refunds()->where('status', 'mocked')->count())->toBe(2);

    expect(fn () => app(RecordMockRefund::class)->handle($paidOrder, 1))
        ->toThrow(ValidationException::class);
});

it('refuses to simulate a mock payment outside local and testing environments', function () {
    $variant = makeAvailableVariant();
    $order = app(PlaceOrder::class)->handle([
        'name' => 'Cliente de prueba', 'phone' => '5512345678', 'email' => null, 'city' => 'Puebla',
        'delivery_method' => 'shipping', 'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ]);
    $confirmed = app(ConfirmOrder::class)->handle($order);
    $confirmed->update(['channel' => 'online']);
    $confirmed->payments()->create([
        'provider' => 'mock',
        'provider_reference' => 'mock_checkout_test',
        'status' => 'pending',
        'amount_cents' => $confirmed->total_cents,
    ]);
    $this->app['env'] = 'production';

    expect(fn () => app(SimulateMockPayment::class)->handle($confirmed))
        ->toThrow(ValidationException::class);
    expect(fn () => app(RecordMockRefund::class)->handle($confirmed, 1))
        ->toThrow(ValidationException::class);
});

it('does not consume a verified payment event until its local payment exists', function () {
    $variant = makeAvailableVariant();
    $order = app(PlaceOrder::class)->handle([
        'name' => 'Cliente de prueba', 'phone' => '5512345678', 'email' => null, 'city' => 'Puebla',
        'delivery_method' => 'shipping', 'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ]);
    $confirmed = app(ConfirmOrder::class)->handle($order);
    $event = new VerifiedPaymentEvent('evt-retry-1', 'provider-ref-retry-1', $confirmed->total_cents, true);
    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('verifyWebhook')->twice()->andReturn($event);
    app()->instance(PaymentGateway::class, $gateway);

    $this->postJson(route('payments.webhook'))
        ->assertStatus(503)
        ->assertJsonPath('accepted', false);
    $this->assertDatabaseMissing('payment_events', ['provider' => 'mock', 'event_id' => 'evt-retry-1']);

    $payment = $confirmed->payments()->create([
        'provider' => 'mock',
        'provider_reference' => 'provider-ref-retry-1',
        'status' => 'pending',
        'amount_cents' => $confirmed->total_cents,
    ]);

    $this->postJson(route('payments.webhook'))
        ->assertOk()
        ->assertJsonPath('accepted', true);
    $this->assertDatabaseHas('payment_events', [
        'provider' => 'mock', 'event_id' => 'evt-retry-1', 'payment_id' => $payment->id,
    ]);
    expect($payment->fresh()->status)->toBe('paid')
        ->and($confirmed->fresh()->status)->toBe(Order::STATUS_PAID);
});

it('returns a mock shipping quote without issuing a label', function () {
    $this->postJson(route('shipping.quote'), ['postal_code' => '06000', 'subtotal_cents' => 45_000])
        ->assertOk()
        ->assertJsonPath('provider', 'mock')
        ->assertJsonPath('service', 'Envío de prueba')
        ->assertJsonPath('price_cents', 9900);
});

it('recalculates and stores the postal shipping quote when placing an order', function () {
    $variant = makeAvailableVariant(price: 80_000);

    $order = app(PlaceOrder::class)->handle([
        'name' => 'Cliente de prueba', 'phone' => '5512345678', 'email' => null, 'city' => 'Puebla',
        'delivery_method' => 'shipping', 'delivery_postal_code' => '72000',
        'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ]);

    expect($order->delivery_postal_code)->toBe('72000')
        ->and($order->shipping_cents)->toBe(9900)
        ->and($order->shipping_needs_confirmation)->toBeFalse()
        ->and($order->total_cents)->toBe(89_900);
});

it('records mock tracking when staff marks a confirmed order shipped', function () {
    $variant = makeAvailableVariant();
    $order = app(PlaceOrder::class)->handle([
        'name' => 'Cliente de prueba', 'phone' => '5512345678', 'email' => null, 'city' => 'Puebla',
        'delivery_method' => 'shipping', 'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ]);
    $confirmed = app(ConfirmOrder::class)->handle($order);

    $shipped = app(UpdateOrderStatus::class)->handle($confirmed, Order::STATUS_SHIPPED);
    expect($shipped->status)->toBe(Order::STATUS_SHIPPED)
        ->and($shipped->shipments()->firstOrFail()->tracking)->toBe('MOCK-'.$order->number)
        ->and($shipped->shipments()->firstOrFail()->label_url)->toBeNull();
});

it('persists shipment state and provider payload from the configured adapter', function () {
    $variant = makeAvailableVariant();
    $order = app(PlaceOrder::class)->handle([
        'name' => 'Cliente de prueba', 'phone' => '5512345678', 'email' => null, 'city' => 'Puebla',
        'delivery_method' => 'shipping', 'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ]);
    $confirmed = app(ConfirmOrder::class)->handle($order);

    $provider = Mockery::mock(ShippingProvider::class);
    $provider->shouldReceive('createShipment')->once()->withArgs(fn (Order $given): bool => $given->id === $confirmed->id)->andReturn([
        'provider' => 'carrier-sandbox',
        'service' => 'Express',
        'tracking' => 'TRACK-123',
        'label_url' => 'https://carrier.test/labels/123',
        'price_cents' => 12_500,
        'status' => 'label_ready',
        'payload' => ['label_id' => 'label-123'],
    ]);
    app()->instance(ShippingProvider::class, $provider);

    $shipped = app(UpdateOrderStatus::class)->handle($confirmed, Order::STATUS_SHIPPED);
    $shipment = $shipped->shipments()->firstOrFail();

    expect($shipment->provider)->toBe('carrier-sandbox')
        ->and($shipment->status)->toBe('label_ready')
        ->and($shipment->payload)->toBe(['label_id' => 'label-123'])
        ->and($shipment->cost_cents)->toBe(12_500);
});

it('shows a signed-in customer an account page and scopes saved addresses to that customer', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user)->get(route('account.index'))->assertOk();
    $customer = Customer::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user)->post(route('account.addresses.store'), [
        'label' => 'Casa', 'recipient' => $user->name, 'phone' => '5512345678', 'postal_code' => '06000',
        'state' => 'CDMX', 'city' => 'Ciudad de México', 'neighborhood' => 'Centro', 'street' => 'Reforma',
        'exterior_number' => '10', 'is_default' => true,
    ])->assertRedirect();
    $this->assertDatabaseHas('addresses', ['customer_id' => $customer->id, 'is_default' => true]);
});

it('decrements stock only on confirmation and returns it on cancellation', function () {
    $variant = makeAvailableVariant(price: 80_000, stock: 5);
    $order = app(PlaceOrder::class)->handle([
        'name' => 'Cliente de prueba',
        'phone' => '5512345678',
        'email' => null,
        'city' => 'Puebla',
        'delivery_method' => 'shipping',
        'items' => [['sku' => $variant->sku, 'quantity' => 2]],
    ]);

    expect($variant->fresh()->stock)->toBe(5);
    $confirmed = app(ConfirmOrder::class)->handle($order);
    expect($confirmed->status)->toBe(Order::STATUS_CONFIRMED)
        ->and($variant->fresh()->stock)->toBe(3);

    $cancelled = app(CancelOrder::class)->handle($confirmed);
    expect($cancelled->status)->toBe(Order::STATUS_CANCELLED)
        ->and($variant->fresh()->stock)->toBe(5)
        ->and($variant->stockMovements()->count())->toBe(2);
});

it('restricts the admin panel to staff roles and requires confirmed two factor authentication', function () {
    $guestAccount = User::factory()->create();
    $this->actingAs($guestAccount)->get('/admin')->assertForbidden();

    $staff = User::factory()->create();
    $staff->forceFill(['email_verified_at' => now()])->save();
    $staff->assignRole(Role::findOrCreate('catalog', 'web'));
    expect($staff->hasRole('catalog'))->toBeTrue()
        ->and($staff->canAccessPanel(app(Panel::class)))->toBeTrue();
    $staff->forceFill(['two_factor_confirmed_at' => now()])->save();
    $this->actingAs($staff)->get('/admin')->assertOk();

    $request = Request::create('/admin');
    $request->setUserResolver(fn () => $staff->forceFill(['two_factor_confirmed_at' => null]));
    $response = app(RequireConfirmedTwoFactor::class)->handle($request, fn () => response('next'));
    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toBe(route('security.edit'));
});

it('records and emails a restock alert when inventory returns', function () {
    Notification::fake();
    $variant = makeAvailableVariant(price: 80_000, stock: 0);

    $this->post(route('restock-alerts.store'), [
        'sku' => $variant->sku,
        'email' => 'notify@example.com',
    ])->assertRedirect();

    $alert = RestockAlert::query()->firstOrFail();
    expect($alert->notified_at)->toBeNull();

    app(AdjustStock::class)->handle($variant, 2, 'receipt');

    Notification::assertSentOnDemand(RestockAvailable::class);
    expect($alert->fresh()->notified_at)->not->toBeNull()
        ->and($variant->fresh()->stock)->toBe(2);
});

it('requires consent and supports signed newsletter confirmation and unsubscribe links', function () {
    Notification::fake();

    $this->post(route('newsletter.store'), ['email' => 'news@example.com'])
        ->assertSessionHasErrors('consent');
    expect(Subscriber::query()->count())->toBe(0);

    $this->post(route('newsletter.store'), ['email' => 'news@example.com', 'consent' => '1'])
        ->assertRedirect();
    $subscriber = Subscriber::query()->firstOrFail();
    expect($subscriber->confirmed_at)->toBeNull();

    $confirmUrl = URL::temporarySignedRoute('newsletter.confirm', now()->addDay(), ['subscriber' => $subscriber->id]);
    $this->get($confirmUrl)->assertRedirect('/');
    expect($subscriber->fresh()->confirmed_at)->not->toBeNull();

    $unsubscribeUrl = URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber->id]);
    $this->get($unsubscribeUrl)->assertRedirect('/');
    expect($subscriber->fresh()->unsubscribed_at)->not->toBeNull();
});
