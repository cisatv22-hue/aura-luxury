<?php

namespace App\Http\Controllers\Store;

use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentGateway;
use App\Payments\VerifiedPaymentEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderPaymentController
{
    public function checkout(string $order, PaymentGateway $gateway): RedirectResponse
    {
        $order = Order::query()->where('number', $order)->firstOrFail();
        abort_unless($order->customer?->user_id === auth()->id(), 404);
        abort_unless($order->status === Order::STATUS_CONFIRMED, 409, 'El pedido debe confirmarse antes de iniciar el pago.');
        $provider = (string) config('integrations.payments.driver');
        $payment = Payment::query()->where('order_id', $order->id)->where('provider', $provider)->where('status', 'pending')->latest()->first();
        if ($payment?->checkout_url) {
            $order->update(['channel' => 'online']);

            return redirect()->away($payment->checkout_url);
        }
        $session = $gateway->createCheckout($order);
        if ($payment) {
            $payment->update(['provider_reference' => $session->reference, 'checkout_url' => $session->url, 'amount_cents' => $order->total_cents]);
        } else {
            Payment::query()->create([
                'order_id' => $order->id,
                'provider' => $session->provider,
                'provider_reference' => $session->reference,
                'checkout_url' => $session->url,
                'status' => 'pending',
                'amount_cents' => $order->total_cents,
            ]);
        }
        $order->update(['channel' => 'online']);

        return redirect()->away($session->url);
    }

    public function webhook(Request $request, PaymentGateway $gateway): JsonResponse
    {
        $event = $gateway->verifyWebhook($request);
        if (! $event instanceof VerifiedPaymentEvent) {
            return response()->json(['accepted' => false], 202);
        }

        $accepted = DB::transaction(function () use ($event): bool {
            $provider = (string) config('integrations.payments.driver');
            $payment = Payment::query()->where('provider', $provider)
                ->where('provider_reference', $event->reference)->lockForUpdate()->first();
            if (! $payment) {
                return false;
            }

            $inserted = DB::table('payment_events')->insertOrIgnore([
                'provider' => $provider,
                'event_id' => $event->eventId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($inserted === 0) {
                return true;
            }
            DB::table('payment_events')->where('provider', $provider)->where('event_id', $event->eventId)
                ->update(['payment_id' => $payment->id]);
            if (! $event->paid || $event->amountCents !== $payment->amount_cents || $payment->status === 'paid') {
                return true;
            }
            $payment->update(['status' => 'paid']);
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);
            if ($order->status === Order::STATUS_CONFIRMED) {
                $order->update(['status' => Order::STATUS_PAID, 'paid_at' => now()]);
                $order->statusChanges()->create(['from_status' => Order::STATUS_CONFIRMED, 'to_status' => Order::STATUS_PAID, 'note' => 'Confirmado por webhook de pago.']);
            }

            return true;
        });

        return response()->json(['accepted' => $accepted], $accepted ? 200 : 503);
    }
}
