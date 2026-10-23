<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Pricing\PriceResolver;
use App\Support\PublicProductData;
use App\Support\StorefrontCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request, ?string $category = null): Response
    {
        return $this->renderCatalog($request, $category, null);
    }

    public function collection(Request $request, string $collection): Response
    {
        return $this->renderCatalog($request, null, $collection);
    }

    public function search(Request $request): Response
    {
        return $this->renderCatalog($request, null, null, 'Resultados de búsqueda');
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->validate(['q' => ['required', 'string', 'min:1', 'max:100']])['q'];

        $suggestions = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('variants', fn ($variants) => $variants->where('is_active', true)->where('stock', '>', 0))
            ->where(fn ($products) => $products
                ->where('name', 'like', '%'.$query.'%')
                ->orWhere('brand_line', 'like', '%'.$query.'%'))
            ->orderBy('name')
            ->limit(6)
            ->get(['name', 'slug'])
            ->map(fn (Product $product) => ['name' => $product->name, 'url' => route('products.show', $product->slug)])
            ->values();

        return response()->json($suggestions);
    }

    private function renderCatalog(Request $request, ?string $category, ?string $collectionSlug, ?string $defaultTitle = null): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'string', 'max:32'],
            'color' => ['nullable', 'string', 'max:80'],
            'min' => ['nullable', 'numeric', 'min:0'],
            'max' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:recent,price_asc,price_desc,name'],
        ]);

        $selectedCategory = $category ? Category::query()->where('slug', $category)->where('is_visible', true)->firstOrFail() : null;
        $collection = $collectionSlug
            ? ProductCollection::query()->where('slug', $collectionSlug)->where('is_visible', true)->firstOrFail()
            : null;

        $query = Product::query()
            ->with(['category', 'colors.variants', 'colors.media', 'variants'])
            ->where('status', Product::STATUS_ACTIVE)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        if ($selectedCategory) {
            $query->where('category_id', $selectedCategory->id);
        }
        if ($collection) {
            $query->whereHas('collections', fn ($relation) => $relation->whereKey($collection->id));
        }
        if (! empty($filters['q'])) {
            $query->where(fn ($search) => $search
                ->where('name', 'like', '%'.$filters['q'].'%')
                ->orWhere('description', 'like', '%'.$filters['q'].'%')
                ->orWhere('brand_line', 'like', '%'.$filters['q'].'%'));
        }
        if (! empty($filters['size'])) {
            $query->whereHas('variants', fn ($variants) => $variants->where('size', $filters['size'])->where('is_active', true));
        }
        if (! empty($filters['color'])) {
            $query->whereHas('colors', fn ($colors) => $colors->where('name', $filters['color']));
        }
        if (isset($filters['min'])) {
            $query->where('base_price_cents', '>=', (int) round($filters['min'] * 100));
        }
        if (isset($filters['max'])) {
            $query->where('base_price_cents', '<=', (int) round($filters['max'] * 100));
        }

        match ($filters['sort'] ?? 'recent') {
            'price_asc' => $query->orderBy('base_price_cents'),
            'price_desc' => $query->orderByDesc('base_price_cents'),
            'name' => $query->orderBy('name'),
            default => $query->latest('published_at'),
        };

        $prices = app(PriceResolver::class);

        $title = 'Tienda';
        if ($collection) {
            $title = $collection->name;
        } elseif ($selectedCategory) {
            $title = $selectedCategory->name;
        } elseif ($defaultTitle !== null) {
            $title = $defaultTitle;
        }

        $cachePage = max(1, $request->integer('page', 1));
        $cacheKey = 'catalog:'.hash('sha256', $request->path().'?'.http_build_query($filters + ['page' => $cachePage]));
        $props = StorefrontCache::remember($cacheKey, function () use ($query, $prices, $filters, $selectedCategory, $title, $defaultTitle, $collection): array {
            $products = $query->paginate(24)->withQueryString();

            return [
                'products' => $products->through(fn ($product) => PublicProductData::from($product, $prices))->toArray(),
                'categories' => Category::query()->where('is_visible', true)->orderBy('position')->get(['name', 'slug'])->toArray(),
                'filters' => $filters + ['category' => $selectedCategory?->slug],
                'title' => $title,
                'searchPage' => $defaultTitle !== null,
                'canonicalUrl' => $collection
                    ? route('collections.show', ['collection' => $collection->slug], false)
                    : ($selectedCategory
                        ? route('catalog.category', ['category' => $selectedCategory->slug], false)
                        : ($defaultTitle ? route('search', [], false) : route('catalog.index', [], false))),
            ];
        });

        return Inertia::render('Catalog/Index', $props);
    }
}
