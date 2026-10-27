<?php

namespace App\Http\Controllers\Store;

use App\Shipping\ShippingCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingQuoteController
{
    public function __invoke(Request $request, ShippingCalculator $shipping): JsonResponse
    {
        $data = $request->validate([
            'postal_code' => ['required', 'string', 'regex:/^\d{5}$/'],
            'subtotal_cents' => ['required', 'integer', 'min:0', 'max:100000000'],
            'weight_grams' => ['sometimes', 'integer', 'min:1', 'max:50000'],
        ]);
        $quote = $shipping->quote(
            (int) $data['subtotal_cents'],
            'shipping',
            null,
            $data['postal_code'],
            (int) ($data['weight_grams'] ?? 1000),
        );

        return response()->json($quote);
    }
}
