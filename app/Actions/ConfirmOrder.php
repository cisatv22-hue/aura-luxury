<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmOrder
{
    public function __construct(private readonly AdjustStock $adjustStock) {}

    public function handle(Order $order, ?User $user = null, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $user, $note): Order {
            $lockedOrder = Order::query()->with('items')->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status === Order::STATUS_CONFIRMED) {
                return $lockedOrder;
            }

            if ($lockedOrder->status !== Order::STATUS_NEW) {
                throw ValidationException::withMessages(['status' => 'Solo se pueden confirmar pedidos nuevos.']);
            }

            foreach ($lockedOrder->items as $item) {
                if (! $item->variant_id) {
                    throw ValidationException::withMessages(['items' => 'El pedido contiene una variante que ya no está disponible.']);
                }

                $variant = ProductVariant::query()->lockForUpdate()->find($item->variant_id);
                if (! $variant || ! $variant->is_active || $variant->stock < $item->quantity) {
                    throw ValidationException::withMessages(['items' => "No hay existencias suficientes para {$item->sku}."]);
                }
            }

            foreach ($lockedOrder->items as $item) {
                $variant = ProductVariant::query()->findOrFail($item->variant_id);
                $this->adjustStock->handle(
                    $variant,
                    -$item->quantity,
                    'sale',
                    $user,
                    $lockedOrder->number,
                    "Pedido confirmado {$lockedOrder->number}",
                );
            }

            $lockedOrder->update(['status' => Order::STATUS_CONFIRMED, 'confirmed_at' => now()]);
            $lockedOrder->statusChanges()->create([
                'from_status' => Order::STATUS_NEW,
                'to_status' => Order::STATUS_CONFIRMED,
                'user_id' => $user?->id,
                'note' => $note,
            ]);

            return $lockedOrder->fresh(['items', 'customer']);
        });
    }
}
