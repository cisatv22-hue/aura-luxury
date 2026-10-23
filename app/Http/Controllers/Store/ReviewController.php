<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);
        $customer = Customer::query()->where('user_id', $request->user()->id)->firstOrFail();
        $order = Order::query()->where('customer_id', $customer->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->latest('delivered_at')->first();

        if (! $order) {
            throw ValidationException::withMessages(['body' => 'Solo puedes reseñar productos de pedidos entregados.']);
        }
        if (Review::query()->where('product_id', $product->id)->where('customer_id', $customer->id)->exists()) {
            throw ValidationException::withMessages(['body' => 'Ya enviaste una reseña para este producto.']);
        }

        Review::query()->create([
            ...$data,
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Gracias. Tu reseña se publicará después de la revisión.');
    }
}
