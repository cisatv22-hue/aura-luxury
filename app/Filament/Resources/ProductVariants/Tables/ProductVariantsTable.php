<?php

namespace App\Filament\Resources\ProductVariants\Tables;

use App\Actions\AdjustStock;
use App\Actions\BulkAdjustVariantPrices;
use App\Models\ProductVariant;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ProductVariantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->searchable(),
                TextColumn::make('color.name')
                    ->label('Color')
                    ->searchable(),
                TextColumn::make('size')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('price_cents')
                    ->label('Precio (MXN)')
                    ->formatStateUsing(fn (?int $state, ProductVariant $record): string => '$'.number_format(($state ?? $record->product->base_price_cents) / 100, 2))
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Existencias')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('low_stock_at')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('adjustStock')
                    ->label('Registrar movimiento')
                    ->icon('heroicon-o-arrows-right-left')
                    ->visible(fn (): bool => auth()->user()?->can('inventory.manage') ?? false)
                    ->schema([
                        TextInput::make('change')->label('Cambio (+ entrada / − salida)')->integer()->required()->rules(['not_in:0']),
                        Select::make('reason')->label('Motivo')->options(['receipt' => 'Entrada', 'adjustment' => 'Ajuste', 'return' => 'Devolución'])->required(),
                        TextInput::make('reference')->label('Referencia')->maxLength(120),
                        Textarea::make('note')->label('Nota')->maxLength(1000),
                    ])
                    ->action(function (ProductVariant $record, array $data): void {
                        app(AdjustStock::class)->handle($record, (int) $data['change'], $data['reason'], auth()->user(), $data['reference'] ?? null, $data['note'] ?? null);
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('adjustPrice')
                    ->label('Ajustar precios seleccionados')
                    ->schema([
                        TextInput::make('percentage')->label('Cambio porcentual (+ sube / − baja)')->numeric()->minValue(-100)->maxValue(100)->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription('Se actualizará el precio de las variantes seleccionadas y se guardará su historial.')
                    ->visible(fn (): bool => auth()->user()?->can('catalog.manage') ?? false)
                    ->action(function (Collection $records, array $data): void {
                        app(BulkAdjustVariantPrices::class)->handle($records, (float) $data['percentage']);
                    }),
            ]);
    }
}
