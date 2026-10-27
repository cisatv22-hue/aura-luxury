<?php

namespace App\Filament\Resources\ShippingRates\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShippingRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('zone')
                    ->required(),
                TextInput::make('price_cents')
                    ->required()
                    ->numeric(),
                TextInput::make('min_days')
                    ->numeric(),
                TextInput::make('max_days')
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
