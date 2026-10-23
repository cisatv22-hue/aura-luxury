<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Order extends Model
{
    use LogsActivity;

    public const STATUS_NEW = 'new';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PAID = 'paid';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'number', 'customer_id', 'coupon_id', 'status', 'channel', 'delivery_method', 'delivery_zone', 'delivery_postal_code',
        'customer_note', 'internal_note', 'subtotal_cents', 'shipping_cents', 'discount_cents',
        'shipping_free', 'shipping_needs_confirmation', 'total_cents', 'confirmed_at', 'paid_at', 'shipped_at', 'delivered_at',
        'stale_reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_cents' => 'integer',
            'shipping_cents' => 'integer',
            'shipping_free' => 'boolean',
            'shipping_needs_confirmation' => 'boolean',
            'discount_cents' => 'integer',
            'total_cents' => 'integer',
            'confirmed_at' => 'datetime',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'stale_reminder_sent_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'delivery_method', 'delivery_zone', 'delivery_postal_code', 'subtotal_cents', 'shipping_cents', 'discount_cents', 'total_cents'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<OrderStatusChange, $this> */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(OrderStatusChange::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<Shipment, $this> */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function canBeCancelled(): bool
    {
        return ! in_array($this->status, [self::STATUS_SHIPPED, self::STATUS_DELIVERED, self::STATUS_CANCELLED], true);
    }
}
