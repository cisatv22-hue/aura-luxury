<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\RestockAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RestockAlertController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:80'],
            'email' => ['nullable', 'email:rfc', 'max:254', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'regex:/^(?:52)?[0-9]{10}$/', 'required_without:email'],
        ]);

        $variant = ProductVariant::query()
            ->where('sku', $data['sku'])
            ->where('is_active', true)
            ->whereHas('product', fn ($product) => $product->where('status', 'active')->whereNotNull('published_at')->where('published_at', '<=', now()))
            ->first();

        if (! $variant) {
            throw ValidationException::withMessages(['sku' => 'No encontramos esa talla disponible para avisos.']);
        }

        $phone = isset($data['phone']) ? preg_replace('/\D+/', '', $data['phone']) : null;
        if ($phone && strlen($phone) === 10) {
            $phone = '52'.$phone;
        }

        RestockAlert::query()->firstOrCreate([
            'variant_id' => $variant->id,
            'email' => $data['email'] ?? null,
            'phone' => $phone,
        ]);

        return back()->with('status', 'restock-alert-created');
    }
}
