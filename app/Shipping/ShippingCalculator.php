<?php

namespace App\Shipping;

use App\Models\ShippingRate;
use App\Settings\StorefrontSettings;

class ShippingCalculator
{
    public function __construct(private readonly ShippingProvider $provider) {}

    /** @return array{price_cents: int, free: bool, needs_confirmation: bool, min_days: ?int, max_days: ?int, provider?: string, service?: string} */
    public function quote(int $subtotalCents, string $deliveryMethod, ?string $zone, ?string $postalCode = null, int $weightGrams = 1000): array
    {
        if ($deliveryMethod === 'pickup') {
            return ['price_cents' => 0, 'free' => true, 'needs_confirmation' => false, 'min_days' => null, 'max_days' => null];
        }

        $settings = app(StorefrontSettings::class);
        if ($subtotalCents >= $settings->free_shipping_threshold_cents) {
            return ['price_cents' => 0, 'free' => true, 'needs_confirmation' => false, 'min_days' => null, 'max_days' => null];
        }

        if ($postalCode !== null) {
            $quote = $this->provider->quote($postalCode, $weightGrams);

            return [
                'price_cents' => $quote->priceCents,
                'free' => false,
                'needs_confirmation' => false,
                'min_days' => $quote->minDays,
                'max_days' => $quote->maxDays,
                'provider' => $quote->provider,
                'service' => $quote->service,
            ];
        }

        if ($settings->shipping_mode === 'confirm' || ! $zone) {
            return ['price_cents' => 0, 'free' => false, 'needs_confirmation' => true, 'min_days' => null, 'max_days' => null];
        }

        $rate = ShippingRate::query()->where('zone', $zone)->where('is_active', true)->first();
        if (! $rate) {
            return ['price_cents' => 0, 'free' => false, 'needs_confirmation' => true, 'min_days' => null, 'max_days' => null];
        }

        return [
            'price_cents' => $rate->price_cents,
            'free' => false,
            'needs_confirmation' => false,
            'min_days' => $rate->min_days,
            'max_days' => $rate->max_days,
        ];
    }
}
