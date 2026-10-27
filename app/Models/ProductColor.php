<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property string|null $image_alt
 * @property string|null $image_path
 */
class ProductColor extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['product_id', 'name', 'hex', 'image_path', 'image_alt', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $format = match (true) {
            function_exists('imagewebp') => 'webp',
            function_exists('imagejpeg') => 'jpg',
            default => 'png',
        };

        $this->addMediaConversion('thumb')->performOnCollections('gallery')->fit(Fit::Max, 400, 500)->format($format);
        $this->addMediaConversion('card')->performOnCollections('gallery')->fit(Fit::Max, 800, 1000)->format($format);
        $this->addMediaConversion('large')->performOnCollections('gallery')->fit(Fit::Max, 1600, 2000)->format($format);
    }
}
