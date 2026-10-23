<?php

namespace App\Payments;

final readonly class CheckoutSession
{
    public function __construct(public string $provider, public string $reference, public string $url) {}
}
