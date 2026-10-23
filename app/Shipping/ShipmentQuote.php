<?php

namespace App\Shipping;

final readonly class ShipmentQuote
{
    public function __construct(public string $provider, public string $service, public int $priceCents, public int $minDays, public int $maxDays) {}
}
