<?php

namespace App\Queries\Catalog;

use App\Models\Product;
use App\Pricing\PriceResolver;
use App\Support\PublicProductData;
use Illuminate\Support\Collection;

class CatalogFeed
{
    /** @return Collection<int, array{id: string, title: string, description: string, link: string, image_link: string, availability: string, price: string, brand: string}> */
    public function rows(): Collection
    {
        $prices = app(PriceResolver::class);

        return Product::query()
            ->with(['category', 'colors.variants', 'colors.media', 'variants.color'])
            ->where('status', Product::STATUS_ACTIVE)
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->whereHas('variants', fn ($query) => $query->where('is_active', true))
            ->orderBy('id')->get()
            ->flatMap(function (Product $product) use ($prices): Collection {
                $public = PublicProductData::from($product, $prices);

                return $product->variants->where('is_active', true)->map(function ($variant) use ($product, $public, $prices): array {
                    $quote = $prices->quote($product, $variant);
                    $description = trim(preg_replace('/\s+/', ' ', strip_tags((string) $product->description)) ?? '');
                    $title = implode(' · ', array_filter([$product->name, $variant->color?->name, $variant->size]));

                    return [
                        'id' => $variant->sku,
                        'title' => $title,
                        'description' => $description,
                        'link' => route('products.show', $product->slug),
                        'image_link' => (string) ($public['image'] ?? ''),
                        'availability' => $variant->stock > 0 ? 'in stock' : 'out of stock',
                        'price' => number_format($quote['price_cents'] / 100, 2, '.', '').' MXN',
                        'brand' => $product->brand_line ?: 'AURA',
                    ];
                });
            })->values();
    }
}
