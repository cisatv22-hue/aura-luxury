<?php

use App\Models\Bundle;
use App\Models\Category;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Pricing\BundlePricing;
use App\Pricing\PriceResolver;
use App\Pricing\PromotionMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makePricingRuleProduct(): Product
{
    static $productNumber = 0;
    $productNumber++;
    $category = Category::create(['name' => 'Accesorios de precio', 'slug' => "accesorios-precio-reglas-{$productNumber}"]);

    return Product::create([
        'category_id' => $category->id,
        'name' => 'Playera de precio',
        'slug' => "playera-precio-reglas-{$productNumber}",
        'description' => 'Producto de prueba',
        'status' => Product::STATUS_ACTIVE,
        'published_at' => now()->subMinute(),
        'base_price_cents' => 10_000,
    ]);
}

function makePriceRulePromotion(array $overrides): Promotion
{
    return Promotion::query()->create(array_merge([
        'name' => 'Promoción de prueba',
        'type' => 'percent',
        'value' => 1_000,
        'priority' => 0,
        'badge_text' => 'Oferta',
        'is_active' => true,
    ], $overrides));
}

it('chooses the highest priority promotion and the lowest price when priorities tie', function () {
    $product = makePricingRuleProduct();
    $highPriority = makePriceRulePromotion(['value' => 1_000, 'priority' => 20]);
    makePriceRulePromotion(['type' => 'price', 'value' => 7_000, 'priority' => 10]);
    $resolver = new PriceResolver(new PromotionMatcher);

    expect($resolver->quote($product)['price_cents'])->toBe(9_000);

    $highPriority->update(['priority' => 10]);
    $tiedResolver = new PriceResolver(new PromotionMatcher);

    expect($tiedResolver->quote($product)['price_cents'])->toBe(7_000);
});

it('shows the crossed-out price only after it was established for 30 days', function () {
    $product = makePricingRuleProduct();
    makePriceRulePromotion(['value' => 2_000]);
    $resolver = new PriceResolver(new PromotionMatcher);

    expect($resolver->quote($product)['compare_at_cents'])->toBeNull();

    PriceHistory::query()->where('product_id', $product->id)->update(['starts_at' => now()->subDays(31)]);

    $establishedResolver = new PriceResolver(new PromotionMatcher);
    expect($establishedResolver->quote($product)['compare_at_cents'])->toBe(10_000);
});

it('ignores promotions outside their active schedule', function () {
    $product = makePricingRuleProduct();
    makePriceRulePromotion(['value' => 2_000, 'starts_at' => now()->addHour()]);
    makePriceRulePromotion(['type' => 'price', 'value' => 6_000, 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]);

    $quote = (new PriceResolver(new PromotionMatcher))->quote($product);

    expect($quote['price_cents'])->toBe(10_000)
        ->and($quote['promotion_id'])->toBeNull();
});

it('calculates bundle savings from variant prices or product base prices', function () {
    $variantProduct = makePricingRuleProduct();
    $variantProduct->update(['base_price_cents' => 10_000]);
    $color = ProductColor::create(['product_id' => $variantProduct->id, 'name' => 'Negro']);
    $variant = ProductVariant::create([
        'product_id' => $variantProduct->id,
        'product_color_id' => $color->id,
        'size' => 'U',
        'sku' => 'AURA-PRICE-BUNDLE-U',
        'price_cents' => 12_000,
        'stock' => 0,
        'is_active' => true,
    ]);
    $basePriceProduct = makePricingRuleProduct();
    $basePriceProduct->update(['base_price_cents' => 5_000]);
    $bundle = Bundle::query()->create([
        'name' => 'Kit de prueba', 'slug' => 'kit-precio-reglas', 'price_cents' => 30_000, 'is_active' => true,
    ]);
    $bundle->items()->create(['product_id' => $variantProduct->id, 'variant_id' => $variant->id, 'qty' => 2]);
    $bundle->items()->create(['product_id' => $basePriceProduct->id, 'qty' => 3]);

    expect(app(BundlePricing::class)->savings($bundle))->toBe(9_000)
        ->and(app(BundlePricing::class)->savings($bundle->fresh()))->toBe(9_000);

    $bundle->update(['price_cents' => 40_000]);
    expect(app(BundlePricing::class)->savings($bundle->fresh()))->toBe(0);
});
