<?php

namespace App\Http\Controllers\Store;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AccountController
{
    public function index(Request $request): Response
    {
        $customer = $this->customer($request->user());

        return Inertia::render('Account/Index', [
            'customer' => ['name' => $customer->name, 'email' => $customer->email, 'phone' => $customer->phone],
            'can_manage_store' => $request->user()->can('catalog.view'),
            'orders' => $customer->orders()->with('items')->latest()->get()->map(fn ($order): array => [
                'number' => $order->number,
                'status' => $order->status,
                'total_cents' => $order->total_cents,
                'created_at' => $order->created_at?->toDateString(),
                'items_count' => $order->items->sum('quantity'),
            ]),
            'addresses' => $customer->addresses()->orderByDesc('is_default')->latest()->get(),
            'favorites' => $customer->wishlistItems()->with('product')->latest()->get()->map(fn ($item): array => [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'slug' => $item->product->slug,
            ]),
        ]);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'], 'recipient' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'], 'postal_code' => ['required', 'regex:/^\d{5}$/'],
            'state' => ['required', 'string', 'max:100'], 'city' => ['required', 'string', 'max:100'],
            'neighborhood' => ['required', 'string', 'max:120'], 'street' => ['required', 'string', 'max:160'],
            'exterior_number' => ['required', 'string', 'max:32'], 'interior_number' => ['nullable', 'string', 'max:32'],
            'references' => ['nullable', 'string', 'max:500'], 'is_default' => ['sometimes', 'boolean'],
        ]);
        $customer = $this->customer($request->user());

        DB::transaction(function () use ($customer, $data): void {
            if (($data['is_default'] ?? false) || ! $customer->addresses()->exists()) {
                $customer->addresses()->update(['is_default' => false]);
                $data['is_default'] = true;
            }
            $customer->addresses()->create($data);
        });

        return back()->with('success', 'Dirección guardada.');
    }

    public function destroyAddress(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->customer_id === $this->customer($request->user())->id, 404);
        $address->delete();

        return back()->with('success', 'Dirección eliminada.');
    }

    public function addFavorite(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === 'active', 404);
        $this->customer($request->user())->wishlistItems()->firstOrCreate(['product_id' => $product->id]);

        return back();
    }

    public function removeFavorite(Request $request, Product $product): RedirectResponse
    {
        $this->customer($request->user())->wishlistItems()->where('product_id', $product->id)->delete();

        return back();
    }

    private function customer(User $user): Customer
    {
        return Customer::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name, 'email' => $user->email],
        );
    }
}
