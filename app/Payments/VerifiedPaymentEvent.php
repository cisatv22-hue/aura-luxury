<?php

namespace App\Payments;

final readonly class VerifiedPaymentEvent
{
    public function __construct(
        public string $eventId,
        public string $reference,
        public int $amountCents,
        public bool $paid,
    ) {}
}
