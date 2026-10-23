<?php

namespace App\Actions;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateProduct
{
    public function handle(Product $product): Product
    {
        return DB::transaction(function () use ($product): Product {
            $product->load(['colors.media', 'variants', 'collections']);
            $copy = $product->replicate();
            $copy->name = $product->name.' (copia)';
            $copy->slug = Str::slug($product->slug.'-copia-'.Str::lower(Str::random(6)));
            $copy->status = Product::STATUS_DRAFT;
            $copy->published_at = null;
            $copy->is_featured = false;
            $copy->save();
            $copy->collections()->sync($product->collections->modelKeys());
            $colorIds = [];

            foreach ($product->colors as $color) {
                $colorCopy = $color->replicate();
                $colorCopy->product_id = $copy->id;
                $colorCopy->save();
                $colorIds[$color->id] = $colorCopy->id;
                foreach ($color->getMedia('gallery') as $media) {
                    $media->copy($colorCopy, 'gallery');
                }
            }

            foreach ($product->variants as $variant) {
                $variantCopy = $variant->replicate();
                $variantCopy->product_id = $copy->id;
                $variantCopy->product_color_id = $colorIds[$variant->product_color_id] ?? null;
                $variantCopy->sku = substr($variant->sku, 0, 70).'-C'.strtoupper(Str::random(6));
                $variantCopy->stock = 0;
                $variantCopy->save();
            }

            return $copy->fresh(['colors', 'variants', 'collections']);
        });
    }
}
