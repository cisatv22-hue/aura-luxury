<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimulateMockPayment
{
    public function handle(Order $order, ?User $user = null): Order
    {
        if (! app()->environment(['local', 'testing']) || config('integrations.payments.driver') !== 'mock') {
            throw ValidationException::withMessages([
                'payment' => 'La simulación solo está disponible en local o testing con el driver mock.',
            ]);
        }

        return DB::transaction(function () use ($order, $user): Order {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $payment = Payment::query()
                ->where('order_id', $lockedOrder->id)
                ->where('provider', 'mock')
                ->where('status', 'pending')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($lockedOrder->status !== Order::STATUS_CONFIRMED
                || $lockedOrder->channel !== 'online'
                || ! $payment
                || $payment->amount_cents !== $lockedOrder->total_cents) {
                throw ValidationException::withMessages([
                    'payment' => 'El pedido debe tener un checkout mock pendiente y un importe vigente.',
                ]);
            }

            $payment->update(['status' => 'paid', 'payload' => ['mock' => true, 'simulated_locally' => true]]);
            $lockedOrder->update(['status' => Order::STATUS_PAID, 'paid_at' => now()]);
            $lockedOrder->statusChanges()->create([
                'from_status' => Order::STATUS_CONFIRMED,
                'to_status' => Order::STATUS_PAID,
                'user_id' => $user?->id,
                'note' => 'Pago mock simulado en entorno local; sin cobro real.',
            ]);

            return $lockedOrder->fresh(['items', 'customer']);
        });
    }
}
