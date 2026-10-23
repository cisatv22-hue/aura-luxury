<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property float $x
 * @property float $y
 */
class LookItem extends Model
{
    protected $fillable = ['look_id', 'product_id', 'x', 'y', 'position'];

    protected function casts(): array
    {
        return ['x' => 'float', 'y' => 'float', 'position' => 'integer'];
    }

    /** @return BelongsTo<Look, $this> */
    public function look(): BelongsTo
    {
        return $this->belongsTo(Look::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
