<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StoreOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = [];

        if (auth()->user()?->can('orders.view')) {
            $stats[] = Stat::make('Pedidos nuevos', Order::query()->where('status', Order::STATUS_NEW)->count())
                ->description('Requieren atención')
                ->color('warning');

            $sales = Order::query()
                ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED])
                ->where('paid_at', '>=', Carbon::now()->startOfMonth())
                ->sum('total_cents');

            $stats[] = Stat::make('Ventas del mes', '$'.number_format($sales / 100, 2).' MXN')
                ->description('Pedidos pagados desde el inicio del mes')
                ->color('success');
        }

        if (auth()->user()?->can('catalog.view')) {
            $stats[] = Stat::make('Productos publicados', Product::query()->where('status', Product::STATUS_ACTIVE)->count())
                ->description('Listos para mostrarse en la tienda')
                ->color('primary');
        }

        if (auth()->user()?->can('inventory.view')) {
            $stats[] = Stat::make('Pocas piezas', ProductVariant::query()->where('is_active', true)->whereColumn('stock', '<=', 'low_stock_at')->where('stock', '>', 0)->count())
                ->description('Variantes en su umbral o por debajo')
                ->color('warning');
            $stats[] = Stat::make('Agotados', ProductVariant::query()->where('is_active', true)->where('stock', 0)->count())
                ->description('Variantes sin existencias');
        }

        if (auth()->user()?->can('promotions.view')) {
            $stats[] = Stat::make('Promociones por vencer', Promotion::query()->where('is_active', true)->whereBetween('ends_at', [now(), now()->addWeek()])->count())
                ->description('Terminan en los próximos siete días');
        }

        if (auth()->user()?->can('reviews.view')) {
            $stats[] = Stat::make('Reseñas por revisar', Review::query()->where('status', 'pending')->count())
                ->description('Pendientes de moderación')
                ->color('warning');
        }

        return $stats;
    }
}
