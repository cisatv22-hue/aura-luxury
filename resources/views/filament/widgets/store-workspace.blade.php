<x-filament-widgets::widget>
    <div class="grid gap-5 xl:grid-cols-[0.9fr_1.1fr]">
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
            <div class="mb-5">
                <p class="text-xs font-medium uppercase tracking-[0.18em] text-primary-600 dark:text-primary-400">AURA · OPERACIÓN</p>
                <h2 class="mt-2 text-xl font-semibold text-gray-950 dark:text-white">¿Qué quieres hacer hoy?</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Accesos rápidos para administrar la tienda.</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($actions as $action)
                    <a href="{{ $action['url'] }}" class="group rounded-xl border border-gray-200 p-4 transition hover:border-primary-500 hover:bg-primary-50/60 dark:border-gray-800 dark:hover:border-primary-500 dark:hover:bg-primary-500/10">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $action['label'] }}</span>
                            <x-filament::icon :icon="$action['icon']" class="size-5 text-gray-500 transition group-hover:text-primary-600 dark:text-gray-400" />
                        </div>
                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $action['description'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>

        @if (auth()->user()?->can('orders.view'))
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">SEGUIMIENTO</p>
                        <h2 class="mt-2 text-xl font-semibold text-gray-950 dark:text-white">Pedidos recientes</h2>
                    </div>
                    <a href="{{ \App\Filament\Resources\Orders\OrderResource::getUrl('index') }}" class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">Ver todos</a>
                </div>

                @forelse ($orders as $order)
                    <a href="{{ \App\Filament\Resources\Orders\OrderResource::getUrl('index') }}" class="flex items-center justify-between gap-4 border-t border-gray-100 py-3 first:border-0 dark:border-gray-800">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-gray-950 dark:text-white">{{ $order->customer?->name ?? 'Cliente' }}</span>
                            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">{{ $order->number }} · {{ $statusLabels[$order->status] ?? $order->status }}</span>
                        </span>
                        <span class="shrink-0 text-sm font-semibold text-gray-950 dark:text-white">${{ number_format($order->total_cents / 100, 2) }}</span>
                    </a>
                @empty
                    <div class="rounded-xl bg-gray-50 px-4 py-8 text-center dark:bg-gray-800/50">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Aún no hay pedidos</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Cuando entre uno, aparecerá aquí para darle seguimiento.</p>
                    </div>
                @endforelse
            </section>
        @endif
    </div>
</x-filament-widgets::widget>
