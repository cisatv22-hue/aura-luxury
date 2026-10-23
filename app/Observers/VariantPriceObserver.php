<?php

namespace App\Observers;

use App\Models\ProductVariant;
use App\Services\PriceHistoryRecorder;

class VariantPriceObserver
{
    public function created(ProductVariant $variant): void
    {
        app(PriceHistoryRecorder::class)->recordVariant($variant, null);
    }

    public function updating(ProductVariant $variant): void
    {
        if ($variant->isDirty('price_cents')) {
            $original = $variant->getOriginal('price_cents');
            $variant->priceBeforeUpdate = $original === null ? null : (int) $original;
        }
    }

    public function updated(ProductVariant $variant): void
    {
        if ($variant->wasChanged('price_cents')) {
            app(PriceHistoryRecorder::class)->recordVariant($variant, $variant->priceBeforeUpdate);
        }

        $variant->priceBeforeUpdate = null;
    }
}
