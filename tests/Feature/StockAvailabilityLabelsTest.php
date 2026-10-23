<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Support\PublicProductData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows low stock per size without hiding it behind other sizes', function () {
    $category = Category::create(['name' => 'Playeras de inventario', 'slug' => 'playeras-inventario-labels']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Playera de inventario',
        'slug' => 'playera-inventario-labels',
        'description' => 'Producto para verificar disponibilidad',
        'status' => Product::STATUS_ACTIVE,
        'published_at' => now()->subMinute(),
        'base_price_cents' => 10_000,
    ]);
    $color = ProductColor::create(['product_id' => $product->id, 'name' => 'Negro']);
    $lowStock = ProductVariant::create([
        'product_id' => $product->id, 'product_color_id' => $color->id, 'size' => 'S',
        'sku' => 'AURA-STOCK-S', 'stock' => 2, 'low_stock_at' => 3, 'is_active' => true,
    ]);
    $available = ProductVariant::create([
        'product_id' => $product->id, 'product_color_id' => $color->id, 'size' => 'M',
        'sku' => 'AURA-STOCK-M', 'stock' => 5, 'low_stock_at' => 3, 'is_active' => true,
    ]);

    $data = PublicProductData::from($product->load(['variants', 'colors.variants']));
    $sizes = collect($data['colors'][0]['sizes'])->keyBy('sku');

    expect($data['available'])->toBeTrue()
        ->and($data['availability_label'])->toBe('Últimas piezas')
        ->and($sizes[$lowStock->sku]['low_stock'])->toBeTrue()
        ->and($sizes[$lowStock->sku]['availability_label'])->toBe('Quedan 2')
        ->and($sizes[$available->sku]['low_stock'])->toBeFalse()
        ->and($sizes[$available->sku]['availability_label'])->toBe('Disponible');
});
