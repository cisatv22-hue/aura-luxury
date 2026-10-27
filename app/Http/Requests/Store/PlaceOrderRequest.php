<?php

namespace App\Http\Requests\Store;

use App\Security\TurnstileVerifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PlaceOrderRequest extends FormRequest
{
    /**
     * @return array{name: string, phone: string, email?: string|null, city: string, delivery_method: string, delivery_zone?: string|null, delivery_postal_code?: string|null, note?: string|null, coupon_code?: string|null, items: array<int, array{sku: string, quantity: int}>}
     */
    public function orderData(): array
    {
        $data = $this->validated();
        foreach (['name', 'phone', 'city', 'delivery_method'] as $field) {
            if (! isset($data[$field]) || ! is_string($data[$field])) {
                throw new \LogicException("Validated order field [{$field}] is missing or invalid.");
            }
        }

        $items = [];
        foreach ($data['items'] as $item) {
            if (! is_array($item) || ! isset($item['sku'], $item['quantity']) || ! is_string($item['sku']) || ! is_int($item['quantity']) && ! (is_string($item['quantity']) && ctype_digit($item['quantity']))) {
                throw new \LogicException('Validated order item is missing or invalid.');
            }
            $items[] = ['sku' => $item['sku'], 'quantity' => (int) $item['quantity']];
        }

        return [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => is_string($data['email'] ?? null) ? $data['email'] : null,
            'city' => $data['city'],
            'delivery_method' => $data['delivery_method'],
            'delivery_zone' => is_string($data['delivery_zone'] ?? null) ? $data['delivery_zone'] : null,
            'delivery_postal_code' => is_string($data['delivery_postal_code'] ?? null) ? $data['delivery_postal_code'] : null,
            'note' => is_string($data['note'] ?? null) ? $data['note'] : null,
            'coupon_code' => is_string($data['coupon_code'] ?? null) ? strtoupper(trim($data['coupon_code'])) : null,
            'items' => $items,
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^(?:52)?[0-9]{10}$/'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'city' => ['required', 'string', 'max:120'],
            'delivery_method' => ['required', 'in:shipping,pickup'],
            'delivery_zone' => ['nullable', 'string', 'max:80'],
            'delivery_postal_code' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'regex:/^\d{5}$/'],
            'note' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:40', 'alpha_dash'],
            'cf-turnstile-response' => ['nullable', 'string', 'max:2048'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.sku' => ['required', 'string', 'max:80', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $turnstile = app(TurnstileVerifier::class);
            if (! $turnstile->enabled()) {
                return;
            }
            $token = $this->input('cf-turnstile-response');
            if (! is_string($token) || $token === '' || ! $turnstile->verify($token, $this->ip())) {
                $validator->errors()->add('cf-turnstile-response', 'Completa la verificación anti-spam e inténtalo de nuevo.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => preg_replace('/\D+/', '', (string) $this->input('phone'))]);
        }
    }
}
