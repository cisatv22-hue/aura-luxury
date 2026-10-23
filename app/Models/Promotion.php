<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = ['name', 'type', 'value', 'priority', 'badge_text', 'starts_at', 'ends_at', 'is_active'];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'priority' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<PromotionTarget, $this> */
    public function targets(): HasMany
    {
        return $this->hasMany(PromotionTarget::class);
    }
}
