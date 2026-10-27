<?php

namespace App\Shipping;

use App\Models\Order;

final class MockShippingProvider implements ShippingProvider
{
    public function quote(string $postalCode, int $weightGrams = 1000): ShipmentQuote
    {
        return new ShipmentQuote('mock', 'Envío de prueba', 9900, 3, 7);
    }

    public function createShipment(Order $order): array
    {
        return [
            'provider' => 'mock',
            'service' => 'Envío de prueba',
            'tracking' => 'MOCK-'.$order->number,
            'label_url' => null,
            'price_cents' => $order->shipping_cents,
            'status' => 'mock',
            'payload' => ['mock' => true],
        ];
    }
}
