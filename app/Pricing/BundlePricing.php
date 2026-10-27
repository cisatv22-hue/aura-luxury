<?php

namespace App\Pricing;

use App\Models\Bundle;

class BundlePricing
{
    public function savings(Bundle $bundle): int
    {
        $bundle->loadMissing('items.variant', 'items.product');

        $piecesPrice = $bundle->items->sum(function ($item): int {
            $unitPrice = $item->variant !== null && $item->variant->price_cents !== null ? $item->variant->price_cents : $item->product->base_price_cents;

            return (int) $unitPrice * $item->qty;
        });

        return max(0, $piecesPrice - $bundle->price_cents);
    }
}
