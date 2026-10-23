<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['slug', 'title', 'body', 'seo_title', 'seo_description', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }
}
