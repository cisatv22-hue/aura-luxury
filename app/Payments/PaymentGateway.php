<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function createCheckout(Order $order): CheckoutSession;

    public function verifyWebhook(Request $request): ?VerifiedPaymentEvent;
}
