<?php

namespace App\Services;

use App\Models\Order;
use App\Settings\StorefrontSettings;
use Illuminate\Support\Facades\URL;

class WhatsAppOrderLink
{
    public function make(Order $order): string
    {
        $order->loadMissing('customer', 'items');
        $settings = app(StorefrontSettings::class);
        $lines = [
            "Hola AURA, acabo de enviar el pedido {$order->number}.",
            "Nombre: {$order->customer?->name}",
            "Celular: {$order->customer?->phone}",
            "Ciudad: {$order->customer?->city}",
            ...($order->delivery_postal_code ? ["Código postal: {$order->delivery_postal_code}"] : []),
            '',
            'Productos:',
        ];

        foreach ($order->items as $item) {
            $lines[] = "{$item->quantity} × {$item->name} ({$item->color} / {$item->size}) — $".number_format($item->line_total_cents / 100, 2);
        }

        $lines[] = '';
        $lines[] = 'Subtotal: $'.number_format($order->subtotal_cents / 100, 2);
        $lines[] = $order->shipping_cents > 0
            ? 'Envío: $'.number_format($order->shipping_cents / 100, 2)
            : ($order->delivery_method === 'pickup' ? 'Entrega: Recolección' : ($order->shipping_free ? 'Envío: gratis' : 'Envío: por confirmar'));
        $lines[] = 'Total: $'.number_format($order->total_cents / 100, 2);
        $lines[] = 'Detalle del pedido: '.URL::route('orders.show', ['order' => $order->number]);

        if ($order->customer_note) {
            $lines[] = "Nota: {$order->customer_note}";
        }

        return 'https://wa.me/'.preg_replace('/\D+/', '', $settings->whatsapp_number).'?text='.rawurlencode(implode("\n", $lines));
    }
}
