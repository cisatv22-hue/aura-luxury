<?php

namespace App\Pricing;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;

class PriceResolver
{
    /** @var array<string, bool> */
    private array $establishedPriceCache = [];

    public function __construct(private readonly PromotionMatcher $promotions) {}

    /** @return array{list_price_cents: int, price_cents: int, compare_at_cents: ?int, discount_cents: int, promotion_id: ?int, badge: ?string} */
    public function quote(Product $product, ?ProductVariant $variant = null): array
    {
        $listPrice = (int) ($variant !== null && $variant->price_cents !== null ? $variant->price_cents : $product->base_price_cents);
        $winner = $this->promotions->forProduct($product)
            ->map(fn (Promotion $promotion) => [
                'promotion' => $promotion,
                'price' => $this->promotions->discountedPrice($promotion, $listPrice),
            ])
            ->sort(function (array $left, array $right): int {
                $priorityOrder = $right['promotion']->priority <=> $left['promotion']->priority;

                return $priorityOrder !== 0 ? $priorityOrder : $left['price'] <=> $right['price'];
            })
            ->first();

        $price = $winner['price'] ?? $listPrice;
        $promotion = $winner['promotion'] ?? null;

        return [
            'list_price_cents' => $listPrice,
            'price_cents' => $price,
            'compare_at_cents' => $promotion && $price < $listPrice && $this->priceWasEstablished($product, $variant, $listPrice) ? $listPrice : null,
            'discount_cents' => max(0, $listPrice - $price),
            'promotion_id' => $promotion?->id,
            'badge' => $price < $listPrice ? $promotion?->badge_text : null,
        ];
    }

    private function priceWasEstablished(Product $product, ?ProductVariant $variant, int $price): bool
    {
        $cacheKey = implode(':', [$product->id, $variant !== null ? $variant->id : 0, $price]);

        if (array_key_exists($cacheKey, $this->establishedPriceCache)) {
            return $this->establishedPriceCache[$cacheKey];
        }

        $minimumStart = now()->subDays(30);

        return $this->establishedPriceCache[$cacheKey] = PriceHistory::query()
            ->where('price_cents', $price)
            ->where('starts_at', '<=', $minimumStart)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->where(function ($query) use ($product, $variant): void {
                $query->where('product_id', $product->id);

                if ($variant) {
                    $query->orWhere('variant_id', $variant->id);
                }
            })
            ->exists();
    }
}
