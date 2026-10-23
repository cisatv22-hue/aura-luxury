<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RecordMockRefund
{
    public function handle(Order $order, int $amountCents, ?string $note = null): Payment
    {
        if (! app()->environment(['local', 'testing']) || config('integrations.payments.driver') !== 'mock') {
            throw ValidationException::withMessages([
                'payment' => 'El registro de reembolsos mock solo está disponible en local o testing.',
            ]);
        }

        return DB::transaction(function () use ($order, $amountCents, $note): Payment {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $payment = $lockedOrder->payments()
                ->where('provider', 'mock')
                ->where('status', 'paid')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! $payment) {
                throw ValidationException::withMessages([
                    'payment' => 'Solo se puede registrar un reembolso mock para un pago mock confirmado.',
                ]);
            }

            $refundedCents = $payment->refunds()
                ->whereIn('status', ['mocked', 'pending', 'approved'])
                ->sum('amount_cents');
            $remainingCents = $payment->amount_cents - $refundedCents;

            if ($amountCents < 1 || $amountCents > $remainingCents) {
                throw ValidationException::withMessages([
                    'amount' => 'El importe debe ser mayor a cero y no exceder el saldo reembolsable.',
                ]);
            }

            $refund = $payment->refunds()->create([
                'provider_reference' => 'mock_refund_'.Str::uuid(),
                'amount_cents' => $amountCents,
                'status' => 'mocked',
                'payload' => [
                    'mock' => true,
                    'note' => $note,
                    'recorded_by' => auth()->id(),
                ],
            ]);

            Log::info('Mock refund recorded', [
                'refund_id' => $refund->id,
                'payment_id' => $payment->id,
                'order_id' => $lockedOrder->id,
                'amount_cents' => $amountCents,
                'user_id' => auth()->id(),
            ]);

            return $payment->fresh('refunds');
        });
    }
}
