<?php

namespace App\Actions;

use App\Models\AbandonedCart;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\NewOrderReceived;
use App\Pricing\PriceResolver;
use App\Settings\StorefrontSettings;
use App\Shipping\ShippingCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    public function __construct(
        private readonly PriceResolver $prices,
        private readonly ShippingCalculator $shipping,
    ) {}

    /**
     * @param  array{name: string, phone: string, email?: string|null, city: string, delivery_method: string, delivery_zone?: string|null, delivery_postal_code?: string|null, note?: string|null, coupon_code?: string|null, items: array<int, array{sku: string, quantity: int}>}  $data
     */
    public function handle(array $data): Order
    {
        return DB::transaction(function () use ($data): Order {
            $phone = preg_replace('/\D+/', '', $data['phone']);
            if (strlen($phone) === 10) {
                $phone = '52'.$phone;
            }
            $customer = Customer::query()->firstOrNew(['phone' => $phone]);
            $customer->fill([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'city' => $data['city'],
            ])->save();
            $authenticatedUser = auth()->user();
            if ($authenticatedUser instanceof User && (
                $customer->user_id === $authenticatedUser->id
                || ($customer->user_id === null && $authenticatedUser->hasVerifiedEmail() && strcasecmp((string) $customer->email, $authenticatedUser->email) === 0)
            )) {
                $customer->user()->associate($authenticatedUser);
                $customer->email = $customer->email ?: $authenticatedUser->email;
                $customer->save();
            }

            $lines = [];
            $subtotal = 0;

            foreach ($data['items'] as $input) {
                $variant = ProductVariant::query()
                    ->with(['product.category', 'product.colors', 'color'])
                    ->where('sku', $input['sku'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $variant || ! $variant->product || $variant->product->status !== 'active' || ! $variant->product->published_at?->isPast()) {
                    throw ValidationException::withMessages(['items' => 'Uno de los productos ya no está disponible. Actualiza la bolsa e inténtalo otra vez.']);
                }

                if ($variant->stock < $input['quantity']) {
                    throw ValidationException::withMessages(['items' => "No hay suficientes existencias para {$variant->sku}. Actualiza la bolsa."]);
                }

                $quote = $this->prices->quote($variant->product, $variant);
                $lineTotal = $quote['price_cents'] * $input['quantity'];
                $subtotal += $lineTotal;
                $lines[] = [
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'color' => $variant->color?->name,
                    'size' => $variant->size,
                    'quantity' => $input['quantity'],
                    'unit_price_cents' => $quote['price_cents'],
                    'line_total_cents' => $lineTotal,
                ];
            }

            $shipping = $this->shipping->quote(
                $subtotal,
                $data['delivery_method'],
                $data['delivery_zone'] ?? null,
                $data['delivery_postal_code'] ?? null,
            );
            $coupon = null;
            $discount = 0;
            if (! empty($data['coupon_code'])) {
                $coupon = Coupon::query()->where('code', strtoupper($data['coupon_code']))->lockForUpdate()->first();
                $discount = $coupon?->discountFor($subtotal) ?? 0;
                if (! $coupon || $discount < 1) {
                    throw ValidationException::withMessages(['coupon_code' => 'El cupón no existe, venció o no cumple el mínimo de compra.']);
                }
                $coupon->increment('usage_count');
            }
            $order = Order::create([
                'customer_id' => $customer->id,
                'coupon_id' => $coupon?->id,
                'status' => Order::STATUS_NEW,
                'channel' => 'whatsapp',
                'delivery_method' => $data['delivery_method'],
                'delivery_zone' => $data['delivery_zone'] ?? null,
                'delivery_postal_code' => $data['delivery_postal_code'] ?? null,
                'customer_note' => $data['note'] ?? null,
                'subtotal_cents' => $subtotal,
                'shipping_cents' => $shipping['price_cents'],
                'shipping_free' => $shipping['free'],
                'shipping_needs_confirmation' => $shipping['needs_confirmation'],
                'discount_cents' => $discount,
                'total_cents' => $subtotal - $discount + $shipping['price_cents'],
            ]);
            $order->update(['number' => sprintf('AURA-%s-%04d', now()->format('ym'), $order->id)]);
            $order->items()->createMany($lines);
            $order->statusChanges()->create(['from_status' => null, 'to_status' => Order::STATUS_NEW]);

            $recipient = app(StorefrontSettings::class)->orders_notification_email;
            if ($recipient) {
                Notification::route('mail', $recipient)->notify(new NewOrderReceived($order));
            }
            $cartEmail = mb_strtolower((string) ($data['email'] ?? ($authenticatedUser instanceof User ? $authenticatedUser->email : '')));
            if ($cartEmail !== '') {
                AbandonedCart::query()->where('email', $cartEmail)->whereNull('recovered_at')->whereNull('unsubscribed_at')->update(['recovered_at' => now()]);
            }

            return $order->fresh(['items', 'customer']);
        });
    }
}
