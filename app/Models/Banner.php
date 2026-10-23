<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = ['title', 'subtitle', 'image_path', 'image_alt', 'link_url', 'position', 'starts_at', 'ends_at', 'is_active'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];
    }
}
