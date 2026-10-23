<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Look;
use App\Models\Order;
use App\Models\Product;
use App\Pricing\PriceResolver;
use App\Support\PublicProductData;
use App\Support\StorefrontCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Home', StorefrontCache::remember('home', function (): array {
            $products = Product::query()
                ->with(['category', 'colors.variants', 'colors.media', 'variants'])
                ->where('status', Product::STATUS_ACTIVE)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->whereHas('variants', fn ($query) => $query->where('is_active', true)->where('stock', '>', 0))
                ->latest('published_at')
                ->get();
            $prices = app(PriceResolver::class);
            $sections = HomeSection::query()->where('is_visible', true)->orderBy('position')->get()->map(function (HomeSection $section) use ($prices) {
                $query = Product::query()
                    ->with(['category', 'colors.variants', 'colors.media', 'variants', 'collections'])
                    ->where('status', Product::STATUS_ACTIVE)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->whereHas('variants', fn ($variants) => $variants->where('is_active', true)->where('stock', '>', 0));

                $bestSellingIds = $section->source_type === 'best_selling'
                    ? DB::table('order_items')
                        ->join('orders', 'orders.id', '=', 'order_items.order_id')
                        ->select('order_items.product_id')
                        ->whereNotNull('order_items.product_id')
                        ->whereIn('orders.status', [Order::STATUS_CONFIRMED, Order::STATUS_PAID, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED])
                        ->where('orders.confirmed_at', '>=', now()->subDays(30))
                        ->groupBy('order_items.product_id')
                        ->orderByRaw('SUM(order_items.quantity) DESC')
                        ->limit($section->limit)
                        ->pluck('order_items.product_id')
                        ->all()
                    : [];

                match ($section->source_type) {
                    'manual' => $query->whereIn('id', $section->source_config['product_ids'] ?? []),
                    'collection' => $query->whereHas('collections', fn ($collections) => $collections->where('slug', $section->source_config['slug'] ?? '')),
                    'low_stock' => $query->whereHas('variants', fn ($variants) => $variants->where('is_active', true)->whereColumn('stock', '<=', 'low_stock_at')->where('stock', '>', 0)),
                    'best_selling' => $query->whereIn('id', $bestSellingIds),
                    default => $query,
                };

                $products = match ($section->source_type) {
                    'best_selling' => $query->get()->sortBy(fn ($product) => array_search($product->id, $bestSellingIds, true)),
                    default => $query->latest('published_at')->take(min(24, max(1, $section->limit)))->get(),
                };

                if ($section->source_type === 'on_sale') {
                    $products = $query->get()->filter(fn ($product) => $prices->quote($product)['discount_cents'] > 0)->take(min(24, max(1, $section->limit)));
                }

                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'products' => $products->map(fn ($product) => PublicProductData::from($product, $prices))->values()->all(),
                ];
            })->values()->all();

            $banners = Banner::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->orderBy('position')
                ->get()
                ->map(fn (Banner $banner) => [
                    'title' => $banner->title,
                    'subtitle' => $banner->subtitle,
                    'image' => $banner->image_path ? Storage::disk('public')->url($banner->image_path) : null,
                    'image_alt' => $banner->image_alt,
                    'link_url' => $banner->link_url,
                ])->values()->all();

            $looks = Look::query()->with(['items.product'])
                ->where('is_visible', true)
                ->orderBy('position')
                ->get()
                ->map(fn (Look $look) => [
                    'id' => $look->id,
                    'title' => $look->title,
                    'image' => Storage::disk('public')->url($look->image_path),
                    'image_alt' => $look->image_alt,
                    'items' => $look->items->filter(fn ($item) => $item->product?->status === Product::STATUS_ACTIVE)
                        ->map(fn ($item) => [
                            'id' => $item->id,
                            'name' => $item->product->name,
                            'url' => route('products.show', $item->product->slug),
                            'x' => (float) $item->x,
                            'y' => (float) $item->y,
                        ])->values()->all(),
                ])->values()->all();

            $newArrivals = $products->take(8);
            $featured = $products
                ->where('is_featured', true)
                ->reject(fn ($product) => $newArrivals->contains('id', $product->id))
                ->take(8);
            $categories = Category::query()
                ->where('is_visible', true)
                ->whereIn('id', $products->pluck('category_id')->unique())
                ->orderBy('position')
                ->get(['id', 'name', 'slug']);
            $categoryHighlights = $categories
                ->map(function (Category $category) use ($products, $prices): ?array {
                    $product = $products->firstWhere('category_id', $category->id);
                    if (! $product) {
                        return null;
                    }

                    $data = PublicProductData::from($product, $prices);

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'image' => $data['image'] ?? ($category->slug === 'chains' ? '/images/chains_gold_silver.webp' : '/images/hero_essentials.webp'),
                        'image_alt' => $product->colors->first()?->image_alt ?: $category->name,
                        'product_name' => $data['name'],
                    ];
                })
                ->filter()
                ->take(3)
                ->values()
                ->all();

            return [
                'categories' => $categories->toArray(),
                'categoryHighlights' => $categoryHighlights,
                'featured' => $featured->map(fn ($product) => PublicProductData::from($product, $prices))->values()->all(),
                'newArrivals' => $newArrivals->map(fn ($product) => PublicProductData::from($product, $prices))->values()->all(),
                'sections' => $sections,
                'banners' => $banners,
                'looks' => $looks,
            ];
        }));
    }
}
