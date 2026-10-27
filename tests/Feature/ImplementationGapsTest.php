<?php

use App\Actions\BulkAdjustVariantPrices;
use App\Actions\ConfirmOrder;
use App\Actions\DuplicateProduct;
use App\Actions\GenerateVariantMatrix;
use App\Actions\PlaceOrder;
use App\Actions\UpdateOrderStatus;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\StorefrontCache;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\E2ESeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeDuplicateableProduct(): Product
{
    $category = Category::create(['name' => 'Accesorios', 'slug' => 'accesorios-duplicate']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Gorra AURA',
        'slug' => 'gorra-aura-duplicate',
        'description' => 'Gorra de prueba',
        'status' => Product::STATUS_ACTIVE,
        'published_at' => now()->subDay(),
        'base_price_cents' => 45_000,
    ]);
    $color = ProductColor::create(['product_id' => $product->id, 'name' => 'Negro']);
    ProductVariant::create([
        'product_id' => $product->id,
        'product_color_id' => $color->id,
        'size' => 'U',
        'sku' => 'AURA-CAP-U',
        'stock' => 9,
        'is_active' => true,
    ]);

    return $product;
}

it('allows customer management for admins and keeps the orders role read-only', function () {
    $this->seed(DatabaseSeeder::class);
    $admin = User::factory()->create(['email_verified_at' => now(), 'two_factor_confirmed_at' => now()]);
    $admin->assignRole(Role::findByName('admin'));
    $orderStaff = User::factory()->create(['email_verified_at' => now(), 'two_factor_confirmed_at' => now()]);
    $orderStaff->assignRole(Role::findByName('orders'));

    $this->actingAs($admin);
    expect(CustomerResource::canCreate())->toBeTrue();

    $this->actingAs($orderStaff);
    expect(CustomerResource::canViewAny())->toBeTrue()
        ->and(CustomerResource::canCreate())->toBeFalse();
});

it('blocks the published E2E catalog seeder outside local and testing', function () {
    config(['app.env' => 'production']);

    expect(fn () => app(E2ESeeder::class)->run())
        ->toThrow(LogicException::class, 'El catálogo E2E solo puede sembrarse en local o testing.');
    expect(Product::query()->where('slug', 'sudadera-prueba-e2e')->exists())->toBeFalse();
});

it('lets an admin link a customer to a verified account from the edit form', function () {
    $this->seed(DatabaseSeeder::class);
    $admin = User::factory()->create(['email_verified_at' => now(), 'two_factor_confirmed_at' => now()]);
    $admin->assignRole(Role::findByName('admin'));
    $customer = Customer::query()->create([
        'name' => 'Cliente legado', 'phone' => '525512345678', 'email' => null, 'city' => 'Puebla',
    ]);
    $account = User::factory()->create(['email' => 'cliente@example.com', 'email_verified_at' => now()]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs($admin);
    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->fillForm(['user_id' => $account->id])
        ->assertSet('data.user_id', $account->id)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($customer->fresh()->user_id)->toBe($account->id);
});

it('lets an admin create a customer from the panel form', function () {
    $this->seed(DatabaseSeeder::class);
    $admin = User::factory()->create(['email_verified_at' => now(), 'two_factor_confirmed_at' => now()]);
    $admin->assignRole(Role::findByName('admin'));
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs($admin);
    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Cliente panel',
            'phone' => '525512345678',
            'email' => 'panel@example.com',
            'city' => 'Puebla',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Customer::query()->where('phone', '525512345678')->where('email', 'panel@example.com')->exists())->toBeTrue();
});

it('prevents admins from linking a customer to an unverified account', function () {
    $this->seed(DatabaseSeeder::class);
    $admin = User::factory()->create(['email_verified_at' => now(), 'two_factor_confirmed_at' => now()]);
    $admin->assignRole(Role::findByName('admin'));
    $customer = Customer::query()->create([
        'name' => 'Cliente legado', 'phone' => '525512345678', 'email' => null, 'city' => 'Puebla',
    ]);
    $unverifiedAccount = User::factory()->create(['email' => 'pendiente@example.com', 'email_verified_at' => null]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs($admin);
    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->fillForm(['user_id' => $unverifiedAccount->id])
        ->call('save')
        ->assertHasFormErrors(['user_id']);

    expect($customer->fresh()->user_id)->toBeNull();
});

it('duplicates product details and variants as an unpublished zero-stock draft with new SKUs', function () {
    $product = makeDuplicateableProduct();

    $copy = app(DuplicateProduct::class)->handle($product);

    expect($copy->id)->not->toBe($product->id)
        ->and($copy->status)->toBe(Product::STATUS_DRAFT)
        ->and($copy->published_at)->toBeNull()
        ->and($copy->base_price_cents)->toBe(45_000)
        ->and($copy->variants)->toHaveCount(1)
        ->and($copy->variants->first()->stock)->toBe(0)
        ->and($copy->variants->first()->sku)->not->toBe('AURA-CAP-U');
});

it('generates only missing color and size combinations with unique SKUs', function () {
    $product = makeDuplicateableProduct();
    $color = $product->colors()->firstOrFail();

    $created = app(GenerateVariantMatrix::class)->handle($product, ['S', 'M', 'L'], 'AURA');
    $createdAgain = app(GenerateVariantMatrix::class)->handle($product, ['S', 'M', 'L'], 'AURA');

    expect($created)->toBe(3)
        ->and($createdAgain)->toBe(0)
        ->and($product->variants()->where('product_color_id', $color->id)->count())->toBe(4)
        ->and($product->variants()->where('stock', 0)->where('size', 'M')->exists())->toBeTrue();
});

it('bulk-adjusts selected variant prices and records the price change', function () {
    $product = makeDuplicateableProduct();
    $variant = $product->variants()->firstOrFail();

    $count = app(BulkAdjustVariantPrices::class)->handle(collect([$variant]), 10);

    expect($count)->toBe(1)
        ->and($variant->fresh()->price_cents)->toBe(49_500)
        ->and($variant->priceHistory()->latest('starts_at')->firstOrFail()->price_cents)->toBe(49_500);
});

it('keeps manual paid transitions for WhatsApp orders', function () {
    $product = makeDuplicateableProduct();
    $variant = $product->variants()->firstOrFail();
    $order = app(PlaceOrder::class)->handle([
        'name' => 'Cliente', 'phone' => '5512345678', 'email' => null, 'city' => 'Puebla',
        'delivery_method' => 'pickup', 'items' => [['sku' => $variant->sku, 'quantity' => 1]],
    ]);
    $confirmed = app(ConfirmOrder::class)->handle($order);

    $paid = app(UpdateOrderStatus::class)->handle($confirmed, Order::STATUS_PAID);

    expect($paid->status)->toBe(Order::STATUS_PAID)
        ->and($paid->paid_at)->not->toBeNull();
});

it('prints packing sheets only for verified staff with two factor and order permission', function () {
    $product = makeDuplicateableProduct();
    $order = Order::query()->create([
        'number' => 'AURA-2610-9911', 'status' => Order::STATUS_NEW, 'channel' => 'whatsapp',
        'delivery_method' => 'pickup', 'subtotal_cents' => 45_000, 'shipping_cents' => 0,
        'total_cents' => 45_000,
    ]);
    $order->items()->create([
        'product_id' => $product->id, 'variant_id' => $product->variants()->first()->id,
        'name' => $product->name, 'sku' => 'AURA-CAP-U', 'quantity' => 1,
        'unit_price_cents' => 45_000, 'line_total_cents' => 45_000,
    ]);
    $customer = User::factory()->create(['email_verified_at' => now(), 'two_factor_confirmed_at' => now()]);
    $this->actingAs($customer)->get(route('admin.orders.packing-slip', ['order' => $order->number]))->assertForbidden();
    $staff = User::factory()->create(['email_verified_at' => now(), 'two_factor_confirmed_at' => now()]);
    $permission = Permission::findOrCreate('orders.view', 'web');
    $staff->assignRole(Role::findOrCreate('orders', 'web'));
    $staff->givePermissionTo($permission);

    $this->actingAs($staff)->get(route('admin.orders.packing-slip', ['order' => $order->number]))
        ->assertOk()->assertSee($order->number)->assertSee($product->name)->assertSee('AURA-CAP-U');
});

it('invalidates cached public storefront data when catalog records change', function () {
    config(['app.env' => 'local', 'cache.default' => 'array']);
    Cache::flush();
    $product = makeDuplicateableProduct();

    $before = StorefrontCache::remember('cache-invalidation-test', fn (): string => 'before');
    $product->update(['name' => 'Gorra AURA actualizada']);
    $after = StorefrontCache::remember('cache-invalidation-test', fn (): string => 'after');

    expect($before)->toBe('before')
        ->and($after)->toBe('after');
});
