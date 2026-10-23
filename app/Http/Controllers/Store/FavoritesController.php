<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Pricing\PriceResolver;
use App\Support\PublicProductData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FavoritesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $products = collect();
        if ($request->user()) {
            $customer = Customer::query()->where('user_id', $request->user()->id)->first();
            $products = $customer?->wishlistItems()->with('product.category', 'product.colors.variants', 'product.colors.media', 'product.variants')
                ->get()->pluck('product')
                ->filter(fn ($product) => $product && $product->status === 'active' && $product->published_at?->isPast())
                ->map(fn ($product) => PublicProductData::from($product, app(PriceResolver::class)))
                ->values() ?? collect();
        }

        return Inertia::render('Favorites', ['savedProducts' => $products]);
    }
}
