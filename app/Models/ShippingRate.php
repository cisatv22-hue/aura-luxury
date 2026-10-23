<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    protected $fillable = ['name', 'zone', 'price_cents', 'min_days', 'max_days', 'is_active'];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'min_days' => 'integer',
            'max_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
