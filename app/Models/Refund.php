<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = ['payment_id', 'provider_reference', 'amount_cents', 'status', 'payload'];

    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'payload' => 'array'];
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
