<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Exportar existencias</x-slot>
            <p class="mb-4 text-sm text-gray-600">Descarga un archivo CSV con una fila por SKU y su existencia actual.</p>
            <x-filament::button wire:click="export" icon="heroicon-o-arrow-down-tray">Descargar CSV</x-filament::button>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Importar existencias</x-slot>
            <p class="mb-4 text-sm text-gray-600">El archivo debe incluir las columnas <code>sku,stock</code>. La importación registra cada diferencia en la bitácora de inventario y valida todos los SKUs antes de aplicar cambios.</p>
            <form wire:submit="import" class="space-y-4">
                {{ $this->form }}
                <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray">Importar CSV</x-filament::button>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
