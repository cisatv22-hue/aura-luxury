<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Order;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class StoreWorkspace extends Widget
{
    protected string $view = 'filament.widgets.store-workspace';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->canAny([
            'catalog.view',
            'orders.view',
            'customers.view',
            'inventory.view',
        ]);
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $actions = [];

        if ($user->can('catalog.view')) {
            $actions[] = [
                'label' => 'Catálogo',
                'description' => 'Edita productos, fotos, tallas y precios.',
                'url' => ProductResource::getUrl('index'),
                'icon' => 'heroicon-o-squares-2x2',
            ];
        }

        if ($user->can('catalog.manage')) {
            $actions[] = [
                'label' => 'Agregar producto',
                'description' => 'Publica una nueva pieza en la tienda.',
                'url' => ProductResource::getUrl('create'),
                'icon' => 'heroicon-o-plus-circle',
            ];
        }

        if ($user->can('orders.view')) {
            $actions[] = [
                'label' => 'Pedidos',
                'description' => 'Revisa y actualiza pedidos de clientes.',
                'url' => OrderResource::getUrl('index'),
                'icon' => 'heroicon-o-clipboard-document-list',
            ];
        }

        if ($user->can('customers.view')) {
            $actions[] = [
                'label' => 'Clientes',
                'description' => 'Consulta contactos y actividad de compra.',
                'url' => CustomerResource::getUrl('index'),
                'icon' => 'heroicon-o-users',
            ];
        }

        $orders = $user->can('orders.view')
            ? Order::query()->with('customer')->latest()->limit(5)->get()
            : new Collection;

        return [
            'actions' => $actions,
            'orders' => $orders,
            'statusLabels' => [
                Order::STATUS_NEW => 'Nuevo',
                Order::STATUS_CONFIRMED => 'Confirmado',
                Order::STATUS_PAID => 'Pagado',
                Order::STATUS_SHIPPED => 'Enviado',
                Order::STATUS_DELIVERED => 'Entregado',
                Order::STATUS_CANCELLED => 'Cancelado',
            ],
        ];
    }
}
