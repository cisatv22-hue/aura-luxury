<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $products = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('id')
            ->get(['slug', 'updated_at']);

        $pages = Page::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('id')
            ->get(['slug', 'updated_at']);

        return response()->view('seo.sitemap', compact('products', 'pages'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
