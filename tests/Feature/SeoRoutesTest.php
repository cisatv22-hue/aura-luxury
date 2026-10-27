<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('permanently redirects the legacy catalog URL to the new storefront', function () {
    $this->get('/catalogo.html')
        ->assertStatus(301)
        ->assertRedirect(route('catalog.index'));
});

it('serves a crawlable sitemap and blocks private pages in robots.txt', function () {
    $sitemap = $this->get(route('sitemap'));

    $sitemap->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('home'), false)
        ->assertSee(route('catalog.index'), false);

    $this->get(route('robots'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /admin')
        ->assertSee(route('sitemap'));
});
