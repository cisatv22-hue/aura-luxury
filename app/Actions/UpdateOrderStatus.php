<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\User;
use App\Shipping\ShippingProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class UpdateOrderStatus
{
    public function __construct(private readonly ShippingProvider $shippingProvider) {}

    private const TRANSITIONS = [
        Order::STATUS_SHIPPED => [Order::STATUS_CONFIRMED, Order::STATUS_PAID],
        Order::STATUS_DELIVERED => [Order::STATUS_SHIPPED],
    ];

    public function handle(Order $order, string $nextStatus, ?User $user = null, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $nextStatus, $user, $note): Order {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $isManualWhatsAppPayment = $nextStatus === Order::STATUS_PAID
                && $locked->status === Order::STATUS_CONFIRMED
                && $locked->channel === 'whatsapp';
            if (! $isManualWhatsAppPayment && ! in_array($locked->status, self::TRANSITIONS[$nextStatus] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Ese cambio de estado no está permitido.']);
            }

            $previousStatus = $locked->status;
            $timestamps = match ($nextStatus) {
                Order::STATUS_PAID => ['paid_at' => now()],
                Order::STATUS_SHIPPED => ['shipped_at' => now()],
                Order::STATUS_DELIVERED => ['delivered_at' => now()],
                default => throw new LogicException('El estado no tiene transición de fecha configurada.'),
            };
            $locked->update(['status' => $nextStatus, ...$timestamps]);
            if ($nextStatus === Order::STATUS_SHIPPED) {
                $shipment = $this->shippingProvider->createShipment($locked);
                $locked->shipments()->firstOrCreate(
                    ['provider' => $shipment['provider']],
                    [
                        'service' => $shipment['service'],
                        'tracking' => $shipment['tracking'],
                        'label_url' => $shipment['label_url'],
                        'cost_cents' => $shipment['price_cents'],
                        'status' => $shipment['status'],
                        'payload' => $shipment['payload'],
                    ],
                );
            }
            $locked->statusChanges()->create([
                'from_status' => $previousStatus,
                'to_status' => $nextStatus,
                'user_id' => $user?->id,
                'note' => $note,
            ]);

            return $locked->fresh(['items', 'customer']);
        });
    }
}
