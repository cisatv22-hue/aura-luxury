<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelOrder
{
    public function __construct(private readonly AdjustStock $adjustStock) {}

    public function handle(Order $order, ?User $user = null, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $user, $note): Order {
            $lockedOrder = Order::query()->with('items')->lockForUpdate()->findOrFail($order->id);

            if (! $lockedOrder->canBeCancelled()) {
                throw ValidationException::withMessages(['status' => 'El pedido ya fue enviado o está cerrado; no se puede cancelar.']);
            }

            $previousStatus = $lockedOrder->status;
            if (in_array($previousStatus, [Order::STATUS_CONFIRMED, Order::STATUS_PAID], true)) {
                foreach ($lockedOrder->items as $item) {
                    if (! $item->variant_id) {
                        continue;
                    }

                    $variant = ProductVariant::query()->find($item->variant_id);
                    if ($variant) {
                        $this->adjustStock->handle(
                            $variant,
                            $item->quantity,
                            'return',
                            $user,
                            $lockedOrder->number,
                            "Cancelación del pedido {$lockedOrder->number}",
                        );
                    }
                }
            }

            $lockedOrder->update(['status' => Order::STATUS_CANCELLED]);
            $lockedOrder->statusChanges()->create([
                'from_status' => $previousStatus,
                'to_status' => Order::STATUS_CANCELLED,
                'user_id' => $user?->id,
                'note' => $note,
            ]);

            return $lockedOrder->fresh(['items', 'customer']);
        });
    }
}
