<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductColor;
use App\Pricing\PriceResolver;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PublicProductData
{
    /** @return array<string, mixed> */
    public static function from(Product $product, ?PriceResolver $prices = null): array
    {
        $prices ??= app(PriceResolver::class);
        $variants = $product->variants->where('is_active', true);
        $available = $variants->sum('stock');
        $threshold = $variants->min('low_stock_at') ?? 3;
        $hasLowStockVariant = $variants->contains(fn ($variant): bool => $variant->stock > 0 && $variant->stock <= $variant->low_stock_at);
        $quotesByVariant = $variants->mapWithKeys(fn ($variant) => [$variant->id => $prices->quote($product, $variant)]);
        $quotes = $quotesByVariant->values();
        $minimumQuote = $quotes->sortBy('price_cents')->first() ?? $prices->quote($product);
        $maximumPrice = $quotes->max('price_cents') ?? $minimumQuote['price_cents'];
        $imageColor = $product->colors->first(fn (ProductColor $color): bool => $color->getFirstMedia('gallery') !== null || $color->image_path !== null);
        $imageMedia = $imageColor?->getFirstMedia('gallery');
        $fallbackImage = $product->category?->slug === 'chains'
            ? '/images/chains_gold_silver.webp'
            : '/images/hero_essentials.webp';
        $image = $imageMedia?->getAvailableUrl(['card', 'original']);
        if (! $image && $imageColor?->image_path) {
            $image = Storage::disk('public')->url($imageColor->image_path);
        }
        if (! $image) {
            $image = $fallbackImage;
        }

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => preg_replace('/^DEMO\s*·\s*/u', '', $product->name) ?: $product->name,
            'description' => $product->description,
            'seo_title' => $product->seo_title,
            'seo_description' => $product->seo_description,
            'price_cents' => $minimumQuote['price_cents'],
            'max_price_cents' => $maximumPrice,
            'compare_at_cents' => $minimumQuote['compare_at_cents'],
            'promotion_badge' => $minimumQuote['badge'],
            'category' => $product->category?->name,
            'available' => $available > 0,
            'availability_label' => $available === 0 ? 'Agotado' : ($available <= $threshold ? "Quedan {$available}" : ($hasLowStockVariant ? 'Últimas piezas' : 'Disponible')),
            'tag' => $available === 0 ? 'Agotado' : ($hasLowStockVariant ? 'Últimas piezas' : ($product->published_at?->isAfter(now()->subDays(14)) ? 'Nuevo' : ($minimumQuote['badge'] ? 'Oferta' : null))),
            'colors' => $product->colors->map(function ($color) use ($quotesByVariant, $fallbackImage, $product) {
                $mediaItems = $color->getMedia('gallery');
                $media = $mediaItems->first();
                $gallery = $mediaItems->map(fn (Media $item): array => [
                    'image' => $item->getAvailableUrl(['card', 'original']),
                    'large' => $item->getAvailableUrl(['large', 'original']),
                    'image_srcset' => self::responsiveSrcset($item),
                    'image_alt' => $item->getCustomProperty('alt') ?? $color->image_alt,
                ])->values()->all();

                return [
                    'name' => $color->name,
                    'hex' => $color->hex,
                    'image' => $media?->getAvailableUrl(['card', 'original']) ?? ($color->image_path ? Storage::disk('public')->url($color->image_path) : $fallbackImage),
                    'image_srcset' => self::responsiveSrcset($media),
                    'image_large' => $media?->getAvailableUrl(['large', 'original']) ?? $fallbackImage,
                    'image_alt' => $media?->getCustomProperty('alt') ?? $color->image_alt ?? $product->name,
                    'gallery' => $gallery ?: [[
                        'image' => $fallbackImage,
                        'large' => $fallbackImage,
                        'image_srcset' => null,
                        'image_alt' => $product->name,
                    ]],
                    'sizes' => $color->variants
                        ->where('is_active', true)
                        ->map(function ($variant) use ($quotesByVariant) {
                            $quote = $quotesByVariant[$variant->id] ?? null;

                            return [
                                'sku' => $variant->sku,
                                'size' => $variant->size,
                                'available' => $variant->stock > 0,
                                'low_stock' => $variant->stock > 0 && $variant->stock <= $variant->low_stock_at,
                                'availability_label' => $variant->stock > 0 && $variant->stock <= $variant->low_stock_at
                                    ? "Quedan {$variant->stock}"
                                    : ($variant->stock === 0 ? 'Agotado' : 'Disponible'),
                                'price_cents' => $quote['price_cents'] ?? null,
                                'compare_at_cents' => $quote['compare_at_cents'] ?? null,
                                'promotion_badge' => $quote['badge'] ?? null,
                            ];
                        })->values(),
                ];
            })->values(),
            'image' => $image,
            'image_srcset' => self::responsiveSrcset($imageMedia),
            'size_guide' => $product->sizeGuide?->is_visible ? [
                'name' => $product->sizeGuide->name,
                'unit' => $product->sizeGuide->unit,
                'rows' => $product->sizeGuide->rows,
            ] : null,
        ];
    }

    private static function responsiveSrcset(?Media $media): ?string
    {
        if (! $media) {
            return null;
        }

        return collect(['thumb' => 400, 'card' => 800, 'large' => 1600])
            ->filter(fn (int $width, string $conversion): bool => $media->hasGeneratedConversion($conversion))
            ->map(fn (int $width, string $conversion): string => $media->getUrl($conversion).' '.$width.'w')
            ->join(', ') ?: null;
    }
}
