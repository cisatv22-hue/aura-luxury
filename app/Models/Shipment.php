<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = ['order_id', 'provider', 'service', 'tracking', 'label_url', 'cost_cents', 'status', 'payload'];

    protected function casts(): array
    {
        return ['cost_cents' => 'integer', 'payload' => 'array'];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
