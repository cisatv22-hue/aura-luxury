<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Look extends Model
{
    protected $fillable = ['title', 'image_path', 'image_alt', 'position', 'is_visible'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'is_visible' => 'boolean'];
    }

    /** @return HasMany<LookItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(LookItem::class)->orderBy('position');
    }
}
