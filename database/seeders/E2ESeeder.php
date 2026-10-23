<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\SizeGuide;
use Illuminate\Database\Seeder;
use LogicException;

class E2ESeeder extends Seeder
{
    public function run(): void
    {
        if (! in_array(config('app.env'), ['local', 'testing'], true)) {
            throw new LogicException('El catálogo E2E solo puede sembrarse en local o testing.');
        }

        $sizeGuide = SizeGuide::query()->updateOrCreate(
            ['name' => 'Guía E2E'],
            [
                'unit' => 'cm',
                'is_visible' => true,
                'rows' => [
                    ['Talla' => 'S', 'Pecho' => 52, '_size' => 'S', '_height_min_cm' => 145, '_height_max_cm' => 165, '_weight_min_kg' => 40, '_weight_max_kg' => 60],
                    ['Talla' => 'M', 'Pecho' => 56, '_size' => 'M', '_height_min_cm' => 166, '_height_max_cm' => 185, '_weight_min_kg' => 61, '_weight_max_kg' => 90],
                ],
            ],
        );

        $category = Category::query()->firstOrCreate(
            ['slug' => 'e2e-sudaderas'],
            ['name' => 'Sudaderas E2E'],
        );

        $product = Product::query()->updateOrCreate(
            ['slug' => 'sudadera-prueba-e2e'],
            [
                'category_id' => $category->id,
                'name' => 'Sudadera de prueba E2E',
                'description' => 'Producto de prueba para verificar el flujo de compra.',
                'status' => Product::STATUS_ACTIVE,
                'published_at' => now()->subMinute(),
                'base_price_cents' => 85000,
                'is_featured' => true,
                'size_guide_id' => $sizeGuide->id,
            ],
        );

        $color = ProductColor::query()->updateOrCreate(
            ['product_id' => $product->id, 'name' => 'Negro'],
            ['hex' => '#111111', 'position' => 0],
        );

        if ($color->getMedia('gallery')->isEmpty()) {
            $extension = match (true) {
                function_exists('imagecreatefromwebp') => 'webp',
                function_exists('imagecreatefromjpeg') => 'jpg',
                default => 'png',
            };

            foreach (["model_negro.{$extension}", "hero_essentials_totalblack.{$extension}"] as $index => $filename) {
                $path = base_path("tests/Fixtures/products/{$filename}");
                if (! is_file($path)) {
                    continue;
                }

                $color->addMedia($path)
                    ->preservingOriginal()
                    ->withCustomProperties(['alt' => 'Sudadera AURA negra, vista '.($index + 1)])
                    ->toMediaCollection('gallery');
            }
        }

        foreach (['S' => 2, 'M' => 8, 'L' => 8] as $size => $stock) {
            ProductVariant::query()->updateOrCreate(
                ['sku' => "AURA-E2E-{$size}"],
                [
                    'product_id' => $product->id,
                    'product_color_id' => $color->id,
                    'size' => $size,
                    'stock' => $stock,
                    'low_stock_at' => 3,
                    'is_active' => true,
                ],
            );
        }

        foreach (range(1, 24) as $index) {
            $listingProduct = Product::query()->updateOrCreate(
                ['slug' => sprintf('sudadera-paginacion-e2e-%02d', $index)],
                [
                    'category_id' => $category->id,
                    'name' => sprintf('Sudadera prueba paginación %02d', $index),
                    'description' => 'Producto de prueba para la paginación del catálogo.',
                    'status' => Product::STATUS_ACTIVE,
                    'published_at' => now()->subMinutes($index + 2),
                    'base_price_cents' => 85000 + $index,
                ],
            );

            $listingColor = ProductColor::query()->updateOrCreate(
                ['product_id' => $listingProduct->id, 'name' => 'Negro'],
                ['hex' => '#111111', 'position' => 0],
            );

            ProductVariant::query()->updateOrCreate(
                ['sku' => sprintf('AURA-E2E-P%02d', $index)],
                [
                    'product_id' => $listingProduct->id,
                    'product_color_id' => $listingColor->id,
                    'size' => 'M',
                    'stock' => 8,
                    'low_stock_at' => 3,
                    'is_active' => true,
                ],
            );
        }
    }
}
