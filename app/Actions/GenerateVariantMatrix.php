<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateVariantMatrix
{
    /** @param list<string> $sizes */
    public function handle(Product $product, array $sizes, ?string $prefix = null): int
    {
        $created = 0;
        DB::transaction(function () use ($product, $sizes, $prefix, &$created): void {
            $product->loadMissing('colors');
            $skuPrefix = Str::upper(Str::substr(Str::slug($prefix ?: $product->slug), 0, 20));

            foreach ($product->colors as $color) {
                foreach (array_unique($sizes) as $size) {
                    $exists = ProductVariant::query()->where('product_id', $product->id)
                        ->where('product_color_id', $color->id)->where('size', $size)->exists();
                    if ($exists) {
                        continue;
                    }
                    $colorCode = Str::upper(Str::substr(Str::slug($color->name), 0, 12));
                    $sizeCode = Str::upper(Str::substr(Str::slug($size), 0, 10));
                    $sku = Str::limit("{$skuPrefix}-{$colorCode}-{$sizeCode}", 72, '');
                    while (ProductVariant::query()->where('sku', $sku)->exists()) {
                        $sku = Str::limit("{$skuPrefix}-{$colorCode}-{$sizeCode}-".Str::upper(Str::random(5)), 80, '');
                    }
                    ProductVariant::query()->create([
                        'product_id' => $product->id,
                        'product_color_id' => $color->id,
                        'size' => $size,
                        'sku' => $sku,
                        'stock' => 0,
                        'low_stock_at' => 3,
                        'is_active' => true,
                    ]);
                    $created++;
                }
            }
        });

        return $created;
    }
}
