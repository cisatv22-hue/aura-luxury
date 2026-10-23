<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use LogicException;

class DemoPreviewSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && ! filter_var(env('AURA_DEMO_SEEDING'), FILTER_VALIDATE_BOOLEAN)) {
            throw new LogicException('En producción, permite esta carga solo con AURA_DEMO_SEEDING=true.');
        }

        $demos = [
            [
                'slug' => 'demo-sudadera-essentials',
                'name' => 'DEMO · Sudadera Essentials',
                'category' => 'hoodies',
                'price' => 85000,
                'color' => 'Negro',
                'hex' => '#111111',
                'sizes' => ['S', 'M', 'L'],
            ],
            [
                'slug' => 'demo-pantalon-streetwear',
                'name' => 'DEMO · Pantalón Streetwear',
                'category' => 'streetwear',
                'price' => 79000,
                'color' => 'Carbón',
                'hex' => '#343434',
                'sizes' => ['M', 'L', 'XL'],
            ],
            [
                'slug' => 'demo-cadena-plata',
                'name' => 'DEMO · Cadena de plata',
                'category' => 'chains',
                'price' => 65000,
                'color' => 'Plata',
                'hex' => '#C0C0C0',
                'sizes' => ['Única'],
            ],
        ];

        $categories = [
            'hoodies' => 'Sudaderas',
            'streetwear' => 'Streetwear',
            'chains' => 'Plata .925',
        ];

        foreach ($categories as $slug => $name) {
            Category::query()->firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        $sampleImages = collect(array_keys($categories))->mapWithKeys(function (string $slug): array {
            $media = ProductColor::query()
                ->whereHas('product', fn ($products) => $products
                    ->where('status', Product::STATUS_DRAFT)
                    ->whereHas('category', fn ($category) => $category->where('slug', $slug)))
                ->whereHas('media', fn ($query) => $query->where('collection_name', 'gallery'))
                ->orderBy('id')
                ->first()
                ?->getFirstMedia('gallery');

            return [$slug => $media];
        });

        foreach ($demos as $index => $demo) {
            $product = Product::query()->updateOrCreate(
                ['slug' => $demo['slug']],
                [
                    'category_id' => Category::query()->where('slug', $demo['category'])->value('id'),
                    'name' => $demo['name'],
                    'description' => 'Artículo de muestra para probar AURA. El precio, la disponibilidad y la descripción son datos simulados; no corresponden al catálogo comercial aprobado.',
                    'care' => 'Dato de demostración.',
                    'material' => 'Muestra de prueba',
                    'status' => Product::STATUS_ACTIVE,
                    'published_at' => now()->subMinutes(3 - $index),
                    'base_price_cents' => $demo['price'],
                    'is_featured' => true,
                    'seo_title' => $demo['name'],
                    'seo_description' => 'Producto de demostración temporal para pruebas de AURA.',
                ],
            );

            $color = ProductColor::query()->updateOrCreate(
                ['product_id' => $product->id, 'name' => $demo['color']],
                [
                    'hex' => $demo['hex'],
                    'image_alt' => preg_replace('/^DEMO\s*·\s*/u', '', $demo['name']).' en tono '.$demo['color'],
                    'position' => 0,
                ],
            );

            $existingMedia = $color->getFirstMedia('gallery');
            $isOldSharedDemoImage = $existingMedia
                && str_starts_with((string) $existingMedia->getCustomProperty('alt'), 'Sudadera Essentials color ');
            $isPreviouslySeededImage = $existingMedia?->getCustomProperty('demo_preview_seed') === true;

            if (! $existingMedia || $isOldSharedDemoImage || $isPreviouslySeededImage) {
                $color->clearMediaCollection('gallery');
                $sampleImage = $sampleImages[$demo['category']] ?? null;
                if ($sampleImage) {
                    $sampleImage->copy($color, 'gallery');
                } else {
                    $fallbackPath = public_path($demo['category'] === 'chains'
                        ? 'images/chains_gold_silver.webp'
                        : 'images/hero_essentials.webp');
                    if (is_file($fallbackPath)) {
                        $color->addMedia($fallbackPath)
                            ->preservingOriginal()
                            ->usingFileName(basename($fallbackPath))
                            ->toMediaCollection('gallery', 'public');
                    }
                }

                $seededMedia = $color->getFirstMedia('gallery');
                if ($seededMedia) {
                    $seededMedia->setCustomProperty('alt', $color->image_alt);
                    $seededMedia->setCustomProperty('demo_preview_seed', true);
                    $seededMedia->save();
                }
            }

            foreach ($demo['sizes'] as $size) {
                $skuSize = $size === 'Única' ? 'U' : strtoupper($size);

                ProductVariant::query()->updateOrCreate(
                    ['sku' => 'DEMO-'.strtoupper($demo['slug']).'-'.$skuSize],
                    [
                        'product_id' => $product->id,
                        'product_color_id' => $color->id,
                        'size' => $size,
                        'price_cents' => null,
                        'stock' => 5,
                        'low_stock_at' => 2,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
