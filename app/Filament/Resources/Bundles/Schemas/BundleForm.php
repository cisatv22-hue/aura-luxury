<?php

namespace App\Filament\Resources\Bundles\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BundleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('price_cents')
                    ->label('Precio del kit (MXN)')
                    ->prefix('$')
                    ->required()
                    ->numeric()
                    ->step('0.01')
                    ->formatStateUsing(fn (?int $state): ?string => $state === null ? null : number_format($state / 100, 2, '.', ''))
                    ->dehydrateStateUsing(fn (string|int|float $state): int => (int) round((float) $state * 100)),
                Repeater::make('items')
                    ->relationship()
                    ->label('Productos incluidos')
                    ->schema([
                        Select::make('product_id')->relationship('product', 'name')->searchable()->preload()->required(),
                        Select::make('variant_id')->relationship('variant', 'sku')->searchable()->preload(),
                        TextInput::make('qty')->numeric()->minValue(1)->default(1)->required(),
                    ])
                    ->columns(3)
                    ->minItems(1)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
