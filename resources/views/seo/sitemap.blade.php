@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ route('home') }}</loc></url>
    <url><loc>{{ route('catalog.index') }}</loc></url>
    @foreach ($products as $product)
        <url>
            <loc>{{ route('products.show', ['product' => $product->slug]) }}</loc>
            <lastmod>{{ $product->updated_at->toAtomString() }}</lastmod>
        </url>
    @endforeach
    @foreach ($pages as $page)
        <url>
            <loc>{{ route('pages.show', ['page' => $page->slug]) }}</loc>
            <lastmod>{{ $page->updated_at->toAtomString() }}</lastmod>
        </url>
    @endforeach
</urlset>
