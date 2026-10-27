<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use Illuminate\Http\Response;

class PackingSlipController
{
    public function __invoke(string $order): Response
    {
        $order = Order::query()->with(['items', 'customer'])->where('number', $order)->firstOrFail();

        return response()->view('admin.packing-slip', ['order' => $order]);
    }
}
