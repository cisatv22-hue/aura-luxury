<?php

namespace App\Filament\Resources\ProductVariants\Schemas;

use App\Models\ProductVariant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductVariantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                Select::make('product_color_id')
                    ->relationship('color', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('size')
                    ->required(),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required(),
                TextInput::make('price_cents')
                    ->label('Precio específico (MXN, opcional)')
                    ->prefix('$')
                    ->numeric()
                    ->step('0.01')
                    ->formatStateUsing(fn (?int $state): ?string => $state === null ? null : number_format($state / 100, 2, '.', ''))
                    ->dehydrateStateUsing(fn (string|int|float|null $state): ?int => $state === null || $state === '' ? null : (int) round((float) $state * 100)),
                TextInput::make('stock')
                    ->label('Existencias (solo usar tras confirmar conteo)')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->disabled(fn (?ProductVariant $record): bool => $record !== null)
                    ->dehydrated(fn (?ProductVariant $record): bool => $record === null)
                    ->helperText('Una vez creada la variante, registra existencias desde Inventario para conservar la bitácora.'),
                TextInput::make('low_stock_at')
                    ->required()
                    ->numeric()
                    ->default(3),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
