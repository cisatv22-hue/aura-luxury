<?php

namespace App\Http\Controllers\Store;

use App\Actions\PlaceOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\PlaceOrderRequest;
use App\Models\Order;
use App\Services\WhatsAppOrderLink;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Order/Create');
    }

    public function store(PlaceOrderRequest $request, PlaceOrder $placeOrder, WhatsAppOrderLink $whatsApp): RedirectResponse
    {
        $order = $placeOrder->handle($request->orderData());

        return to_route('orders.show', ['order' => $order->number])
            ->with('whatsapp_url', $whatsApp->make($order));
    }

    public function show(string $order): Response
    {
        $order = Order::query()->with(['items', 'shipments', 'coupon'])->where('number', $order)->firstOrFail();

        return Inertia::render('Order/Show', [
            'order' => [
                'number' => $order->number,
                'status' => $order->status,
                'delivery_method' => $order->delivery_method,
                'delivery_postal_code' => $order->delivery_postal_code,
                'subtotal_cents' => $order->subtotal_cents,
                'shipping_cents' => $order->shipping_cents,
                'discount_cents' => $order->discount_cents,
                'coupon_code' => $order->coupon?->code,
                'shipping_free' => $order->shipping_free,
                'shipping_needs_confirmation' => $order->shipping_needs_confirmation,
                'total_cents' => $order->total_cents,
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'color' => $item->color,
                    'size' => $item->size,
                    'quantity' => $item->quantity,
                    'line_total_cents' => $item->line_total_cents,
                ]),
            ],
            'whatsappUrl' => session('whatsapp_url'),
            'canStartPayment' => auth()->user()?->hasVerifiedEmail()
                && $order->customer?->user_id === auth()->id()
                && $order->status === Order::STATUS_CONFIRMED,
            'paymentMode' => request()->query('payment') === 'mock' && config('integrations.payments.driver') === 'mock',
            'shipment' => $order->shipments->first()?->only(['service', 'tracking', 'label_url', 'status']),
        ]);
    }
}
