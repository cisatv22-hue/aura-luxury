<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function show(string $page): Response
    {
        $page = Page::query()
            ->where('slug', $page)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        return Inertia::render('Info/Show', [
            'page' => [
                'title' => $page->title,
                'body' => $page->body,
                'seo_title' => $page->seo_title,
                'seo_description' => $page->seo_description,
            ],
        ]);
    }
}
