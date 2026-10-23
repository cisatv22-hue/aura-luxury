<?php

namespace App\Pricing;

use App\Models\Category;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionTarget;
use Illuminate\Support\Collection;

class PromotionMatcher
{
    /** @var array<int, Collection<int, Promotion>> */
    private array $matchesByProduct = [];

    /** @return Collection<int, Promotion> */
    public function forProduct(Product $product): Collection
    {
        if (isset($this->matchesByProduct[$product->id])) {
            return $this->matchesByProduct[$product->id];
        }

        $now = now();
        $collectionIds = $product->relationLoaded('collections')
            ? $product->collections->modelKeys()
            : $product->collections()->pluck('collections.id')->all();
        $promotions = Promotion::query()
            ->with('targets')
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->get();

        return $this->matchesByProduct[$product->id] = $promotions->filter(function (Promotion $promotion) use ($product, $collectionIds): bool {
            if ($promotion->targets->isEmpty()) {
                return true;
            }

            return $promotion->targets->contains(function (PromotionTarget $target) use ($product, $collectionIds): bool {
                return match ($target->target_type) {
                    Product::class => (int) $target->target_id === (int) $product->id,
                    Category::class => (int) $target->target_id === (int) $product->category_id,
                    ProductCollection::class => in_array((int) $target->target_id, $collectionIds, true),
                    default => false,
                };
            });
        })->values();
    }

    public function discountedPrice(Promotion $promotion, int $listPriceCents): int
    {
        return match ($promotion->type) {
            'percent' => max(0, $listPriceCents - intdiv($listPriceCents * min($promotion->value, 10_000), 10_000)),
            'amount' => max(0, $listPriceCents - $promotion->value),
            'price' => min($listPriceCents, $promotion->value),
            default => $listPriceCents,
        };
    }
}
