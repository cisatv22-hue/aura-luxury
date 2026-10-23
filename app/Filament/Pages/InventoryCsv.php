<?php

namespace App\Filament\Pages;

use App\Actions\AdjustStock;
use App\Models\ProductVariant;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryCsv extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.inventory-csv';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Importar / exportar inventario';

    protected static string|\UnitEnum|null $navigationGroup = 'Operaciones';

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('inventory.manage') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('csv_file')
                ->label('Archivo CSV de existencias')
                ->disk('local')
                ->directory('inventory-imports')
                ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                ->maxSize(2048)
                ->required(),
        ])->statePath('data');
    }

    public function import(AdjustStock $adjustStock): void
    {
        $path = $this->formSchema()->getState()['csv_file'] ?? null;

        if (! is_string($path) || ! Storage::disk('local')->exists($path)) {
            throw ValidationException::withMessages(['data.csv_file' => 'Selecciona un CSV válido.']);
        }

        $handle = fopen(Storage::disk('local')->path($path), 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['data.csv_file' => 'No se pudo leer el archivo.']);
        }

        try {
            $header = fgetcsv($handle);
            if ($header === false || array_map(fn ($value) => strtolower(trim((string) $value)), $header) !== ['sku', 'stock']) {
                throw ValidationException::withMessages(['data.csv_file' => 'El encabezado debe ser: sku,stock.']);
            }

            $rows = [];
            $line = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if (count($row) !== 2 || trim((string) $row[0]) === '' || filter_var($row[1], FILTER_VALIDATE_INT) === false || (int) $row[1] < 0) {
                    throw ValidationException::withMessages(['data.csv_file' => "La línea {$line} requiere un SKU y una existencia entera igual o mayor a cero."]);
                }
                $sku = trim((string) $row[0]);
                if (isset($rows[$sku])) {
                    throw ValidationException::withMessages(['data.csv_file' => "El SKU {$sku} está repetido en la línea {$line}."]);
                }
                $rows[$sku] = (int) $row[1];
            }
        } finally {
            fclose($handle);
            Storage::disk('local')->delete($path);
        }

        DB::transaction(function () use ($rows, $adjustStock): void {
            foreach ($rows as $sku => $stock) {
                $variant = ProductVariant::query()->where('sku', $sku)->lockForUpdate()->first();
                if (! $variant) {
                    throw ValidationException::withMessages(['data.csv_file' => "No existe la variante con SKU {$sku}; no se aplicaron cambios."]);
                }

                $difference = $stock - $variant->stock;
                if ($difference !== 0) {
                    $adjustStock->handle($variant, $difference, $difference > 0 ? 'receipt' : 'adjustment', auth()->user(), 'Importación CSV', "Existencias establecidas en {$stock} desde CSV.");
                }
            }
        });

        $this->formSchema()->fill();
        Notification::make()->title('Existencias importadas')->body(count($rows).' variantes procesadas.')->success()->send();
    }

    public function export(): StreamedResponse
    {
        $filename = 'aura-inventario-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'wb');
            if (! is_resource($stream)) {
                return;
            }
            fputcsv($stream, ['sku', 'stock']);
            ProductVariant::query()->orderBy('sku')->chunk(500, function ($variants) use ($stream): void {
                foreach ($variants as $variant) {
                    fputcsv($stream, [$variant->sku, $variant->stock]);
                }
            });
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function formSchema(): Schema
    {
        return $this->getSchema('form') ?? throw new \LogicException('El formulario de inventario no está disponible.');
    }
}
