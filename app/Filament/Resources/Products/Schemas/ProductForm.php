<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name'),
                Select::make('collections')
                    ->relationship('collections', 'name')
                    ->label('Colecciones')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('brand_line'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Textarea::make('care')
                    ->columnSpanFull(),
                TextInput::make('material'),
                Select::make('status')
                    ->options(['draft' => 'Borrador', 'active' => 'Publicado', 'archived' => 'Archivado'])
                    ->required()
                    ->default('draft'),
                DateTimePicker::make('published_at'),
                TextInput::make('base_price_cents')
                    ->label('Precio base (MXN)')
                    ->prefix('$')
                    ->suffix('MXN')
                    ->numeric()
                    ->step('0.01')
                    ->minValue(0.01)
                    ->required()
                    ->formatStateUsing(fn (?int $state): ?string => $state === null ? null : number_format($state / 100, 2, '.', ''))
                    ->dehydrateStateUsing(fn (string|int|float $state): int => (int) round((float) $state * 100)),
                Toggle::make('is_featured')
                    ->required(),
                TextInput::make('seo_title'),
                TextInput::make('seo_description'),
                Select::make('size_guide_id')
                    ->relationship('sizeGuide', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
