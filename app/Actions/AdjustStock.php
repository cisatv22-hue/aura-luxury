<?php

namespace App\Actions;

use App\Models\ProductVariant;
use App\Models\RestockAlert;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\RestockAvailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AdjustStock
{
    private const REASONS = ['receipt', 'adjustment', 'sale', 'return'];

    public function handle(
        ProductVariant $variant,
        int $change,
        string $reason,
        ?User $user = null,
        ?string $reference = null,
        ?string $note = null,
    ): StockMovement {
        if ($change === 0 || ! in_array($reason, self::REASONS, true)) {
            throw new InvalidArgumentException('El movimiento debe tener una cantidad distinta de cero y un motivo válido.');
        }

        return DB::transaction(function () use ($variant, $change, $reason, $user, $reference, $note): StockMovement {
            $lockedVariant = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $newStock = $lockedVariant->stock + $change;

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'stock' => "No hay existencias suficientes para {$lockedVariant->sku}.",
                ]);
            }

            $lockedVariant->update(['stock' => $newStock]);

            if ($change > 0 && $newStock > 0) {
                RestockAlert::query()
                    ->with('variant.product')
                    ->where('variant_id', $lockedVariant->id)
                    ->whereNull('notified_at')
                    ->whereNotNull('email')
                    ->get()
                    ->each(function (RestockAlert $alert): void {
                        Notification::route('mail', $alert->email)->notify(new RestockAvailable($alert));
                        $alert->update(['notified_at' => now()]);
                    });
            }

            return StockMovement::create([
                'variant_id' => $lockedVariant->id,
                'change' => $change,
                'reason' => $reason,
                'reference' => $reference,
                'user_id' => $user?->id,
                'note' => $note,
            ]);
        });
    }
}
