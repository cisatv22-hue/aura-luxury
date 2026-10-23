<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\PriceHistoryRecorder;

class ProductPriceObserver
{
    public function created(Product $product): void
    {
        app(PriceHistoryRecorder::class)->recordProduct($product);
    }

    public function updating(Product $product): void
    {
        if ($product->isDirty('base_price_cents')) {
            $product->priceBeforeUpdate = (int) $product->getOriginal('base_price_cents');
        }
    }

    public function updated(Product $product): void
    {
        if ($product->wasChanged('base_price_cents')) {
            app(PriceHistoryRecorder::class)->recordProduct($product, $product->priceBeforeUpdate);
        }

        $product->priceBeforeUpdate = null;
    }
}
