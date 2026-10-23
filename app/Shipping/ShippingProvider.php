<?php

namespace App\Shipping;

use App\Models\Order;

interface ShippingProvider
{
    public function quote(string $postalCode, int $weightGrams = 1000): ShipmentQuote;

    /** @return array{provider: string, service: string, tracking: string, label_url: ?string, price_cents: int, status: string, payload: array<string, mixed>} */
    public function createShipment(Order $order): array;
}
