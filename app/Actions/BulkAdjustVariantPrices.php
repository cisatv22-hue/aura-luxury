<?php

namespace App\Actions;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BulkAdjustVariantPrices
{
    /** @param Collection<int, ProductVariant> $variants */
    public function handle(Collection $variants, float $percentage): int
    {
        if ($percentage < -100 || $percentage > 100) {
            throw new \InvalidArgumentException('El ajuste debe estar entre -100% y 100%.');
        }

        return DB::transaction(function () use ($variants, $percentage): int {
            $updated = 0;
            foreach ($variants as $variant) {
                $current = $variant->price_cents ?? $variant->product->base_price_cents;
                $next = (int) round($current * (1 + ($percentage / 100)));
                if ($next < 1) {
                    continue;
                }
                $variant->update(['price_cents' => $next]);
                $updated++;
            }

            return $updated;
        });
    }
}
