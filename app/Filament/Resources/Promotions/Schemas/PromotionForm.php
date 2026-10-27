<?php

namespace App\Filament\Resources\Promotions\Schemas;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('type')
                    ->options(['percent' => 'Porcentaje', 'amount' => 'Descuento en MXN', 'price' => 'Precio fijo en MXN'])
                    ->required(),
                TextInput::make('value')
                    ->label('Valor (porcentaje: 20% = 2000; monto o precio fijo: centavos)')
                    ->required()
                    ->numeric(),
                TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('badge_text'),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                Toggle::make('is_active')
                    ->required(),
                Repeater::make('targets')
                    ->relationship()
                    ->label('Alcance (vacío aplica a toda la tienda)')
                    ->schema([
                        Select::make('target_type')->label('Tipo')->options([
                            Product::class => 'Producto',
                            Category::class => 'Categoría',
                            Collection::class => 'Colección',
                        ])->required(),
                        TextInput::make('target_id')->label('ID del elemento')->integer()->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
