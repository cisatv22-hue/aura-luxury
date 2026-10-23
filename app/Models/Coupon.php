<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int $value
 * @property int $minimum_subtotal_cents
 * @property int $usage_count
 * @property int|null $usage_limit
 * @property bool $is_active
 */
class Coupon extends Model
{
    protected $fillable = ['code', 'type', 'value', 'minimum_subtotal_cents', 'usage_limit', 'usage_count', 'starts_at', 'ends_at', 'is_active'];

    protected function casts(): array
    {
        return ['value' => 'integer', 'minimum_subtotal_cents' => 'integer', 'usage_limit' => 'integer', 'usage_count' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function discountFor(int $subtotalCents): int
    {
        if (! $this->is_active
            || ($this->starts_at && $this->starts_at->isFuture())
            || ($this->ends_at && $this->ends_at->isPast())
            || $subtotalCents < $this->minimum_subtotal_cents
            || ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit)) {
            return 0;
        }

        $discount = match ($this->type) {
            'percent' => (int) floor($subtotalCents * min($this->value, 10000) / 10000),
            'amount' => min($this->value, $subtotalCents),
            default => 0,
        };

        return min($discount, $subtotalCents);
    }
}
