<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property array<string, mixed>|null $source_config */
class HomeSection extends Model
{
    protected $fillable = ['title', 'source_type', 'source_config', 'limit', 'position', 'is_visible'];

    protected function casts(): array
    {
        return ['source_config' => 'array', 'limit' => 'integer', 'position' => 'integer', 'is_visible' => 'boolean'];
    }
}
