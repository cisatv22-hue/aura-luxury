<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\ProductVariant;
use App\Pricing\PriceResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AbandonedCartController extends Controller
{
    public function store(Request $request, PriceResolver $prices): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
            'consent' => ['accepted'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.sku' => ['required', 'string', 'max:80', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);
        $rawItems = $data['items'] ?? null;
        if (! is_array($rawItems)) {
            return back()->withErrors(['items' => 'La bolsa no es válida.']);
        }
        $itemInputs = [];
        foreach ($rawItems as $rawItem) {
            if (! is_array($rawItem) || ! is_string($rawItem['sku'] ?? null) || ! is_numeric($rawItem['quantity'] ?? null)) {
                return back()->withErrors(['items' => 'La bolsa no es válida.']);
            }
            $itemInputs[] = ['sku' => $rawItem['sku'], 'quantity' => (int) $rawItem['quantity']];
        }
        $variants = ProductVariant::query()->with(['product', 'color'])->where('is_active', true)
            ->whereIn('sku', array_column($itemInputs, 'sku'))->get()->keyBy('sku');
        if ($variants->count() !== count($itemInputs)) {
            return back()->withErrors(['items' => 'Actualiza la bolsa antes de solicitar el recordatorio.']);
        }
        $items = collect($itemInputs)->map(function (array $item) use ($variants, $prices): array {
            $variant = $variants->get($item['sku']);
            abort_unless($variant?->product?->status === 'active', 422);
            $quote = $prices->quote($variant->product, $variant);

            return ['sku' => $variant->sku, 'name' => $variant->product->name, 'color' => $variant->color?->name, 'size' => $variant->size, 'quantity' => (int) $item['quantity'], 'unit_price_cents' => $quote['price_cents']];
        })->all();
        $email = mb_strtolower($data['email']);
        $cart = AbandonedCart::query()->where('email', $email)->whereNull('reminder_sent_at')->whereNull('recovered_at')->whereNull('unsubscribed_at')->where('created_at', '>=', now()->subDay())->latest()->first();
        if ($cart) {
            $cart->update(['items' => $items, 'consented_at' => now()]);
        } else {
            $cart = AbandonedCart::query()->create(['token' => (string) Str::uuid(), 'email' => $email, 'items' => $items, 'consented_at' => now()]);
        }

        return back()->with('cart_reminder_saved', true);
    }

    public function unsubscribe(AbandonedCart $cart): RedirectResponse
    {
        $cart->update(['unsubscribed_at' => now(), 'email' => '', 'items' => []]);

        return to_route('home')->with('status', 'Se eliminó el recordatorio de tu bolsa.');
    }
}
