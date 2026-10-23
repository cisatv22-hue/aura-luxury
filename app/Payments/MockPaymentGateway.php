<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class MockPaymentGateway implements PaymentGateway
{
    public function createCheckout(Order $order): CheckoutSession
    {
        $reference = 'mock_'.Str::uuid();

        return new CheckoutSession('mock', $reference, route('orders.show', $order->number).'?payment=mock');
    }

    public function verifyWebhook(Request $request): ?VerifiedPaymentEvent
    {
        // The mock driver never accepts public callbacks as proof of payment.
        return null;
    }
}
