<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number'),
                Select::make('customer_id')
                    ->relationship('customer', 'name'),
                TextInput::make('status')
                    ->required()
                    ->default('new'),
                TextInput::make('channel')
                    ->required()
                    ->default('whatsapp'),
                TextInput::make('delivery_method')
                    ->required()
                    ->default('shipping'),
                TextInput::make('delivery_zone'),
                TextInput::make('delivery_postal_code')->disabled(),
                Textarea::make('customer_note')
                    ->columnSpanFull(),
                Textarea::make('internal_note')
                    ->columnSpanFull(),
                TextInput::make('subtotal_cents')
                    ->required()
                    ->numeric(),
                TextInput::make('shipping_cents')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('discount_cents')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_cents')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('confirmed_at'),
                DateTimePicker::make('paid_at'),
                DateTimePicker::make('shipped_at'),
                DateTimePicker::make('delivered_at'),
                Toggle::make('shipping_free')
                    ->required(),
                Toggle::make('shipping_needs_confirmation')
                    ->required(),
            ]);
    }
}
