<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Pricing\PriceResolver;
use App\Support\PublicProductData;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(Product $product): Response
    {
        abort_unless(
            $product->status === Product::STATUS_ACTIVE && $product->published_at?->isPast(),
            404,
        );

        $product->load(['category', 'colors.variants', 'colors.media', 'variants', 'sizeGuide']);
        $publishedReviews = $product->reviews()->where('status', 'approved')->with('customer')->latest()->get();
        $customer = auth()->user()?->customer;
        $canReview = auth()->user()?->hasVerifiedEmail() && $customer && $customer->orders()->where('status', Order::STATUS_DELIVERED)
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))->exists()
            && ! $customer->reviews()->where('product_id', $product->id)->exists();
        $prices = app(PriceResolver::class);
        $related = Product::query()
            ->with(['category', 'colors.variants', 'colors.media', 'variants'])
            ->where('status', Product::STATUS_ACTIVE)
            ->whereNotNull('published_at')
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->whereHas('variants', fn ($query) => $query->where('is_active', true)->where('stock', '>', 0))
            ->latest('published_at')
            ->take(4)
            ->get()
            ->map(fn ($relatedProduct) => PublicProductData::from($relatedProduct, $prices))
            ->values();

        return Inertia::render('Product/Show', [
            'product' => PublicProductData::from($product, $prices),
            'related' => $related,
            'sizeGuide' => PublicProductData::from($product, $prices)['size_guide'],
            'reviews' => $publishedReviews->map(fn ($review): array => ['rating' => $review->rating, 'title' => $review->title, 'body' => $review->body, 'customer' => $review->customer?->name]),
            'canReview' => (bool) $canReview,
        ]);
    }
}
