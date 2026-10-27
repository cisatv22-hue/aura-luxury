<?php

namespace App\Services;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class PriceHistoryRecorder
{
    public function recordProduct(Product $product, ?int $previousPrice = null): void
    {
        $this->record('product_id', $product->id, (int) $product->base_price_cents, $previousPrice);
    }

    public function recordVariant(ProductVariant $variant, ?int $previousPrice): void
    {
        $this->record('variant_id', $variant->id, $variant->price_cents === null ? null : (int) $variant->price_cents, $previousPrice);
    }

    private function record(string $ownerColumn, int $ownerId, ?int $price, ?int $previousPrice): void
    {
        DB::transaction(function () use ($ownerColumn, $ownerId, $price, $previousPrice): void {
            $query = PriceHistory::query()->where($ownerColumn, $ownerId)->whereNull('ends_at');
            $current = $query->latest('starts_at')->first();

            if ($current && $price !== null && $current->price_cents === $price) {
                return;
            }

            if ($current) {
                $current->update(['ends_at' => now()]);
            } elseif ($previousPrice !== null) {
                PriceHistory::create([
                    $ownerColumn => $ownerId,
                    'price_cents' => $previousPrice,
                    'starts_at' => now(),
                    'ends_at' => now(),
                ]);
            }

            if ($price !== null) {
                PriceHistory::create([
                    $ownerColumn => $ownerId,
                    'price_cents' => $price,
                    'starts_at' => now(),
                ]);
            }
        });
    }
}
