<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property array<int, array{sku: string, name: string, color: string|null, size: string|null, quantity: int, unit_price_cents: int}> $items */
class AbandonedCart extends Model
{
    protected $fillable = ['token', 'email', 'items', 'consented_at', 'reminder_sent_at', 'recovered_at', 'unsubscribed_at'];

    protected function casts(): array
    {
        return ['items' => 'array', 'consented_at' => 'datetime', 'reminder_sent_at' => 'datetime', 'recovered_at' => 'datetime', 'unsubscribed_at' => 'datetime'];
    }
}
