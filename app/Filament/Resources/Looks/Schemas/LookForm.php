<?php

namespace App\Filament\Resources\Looks\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LookForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                FileUpload::make('image_path')
                    ->image()
                    ->disk('public')
                    ->directory('looks')
                    ->visibility('public')
                    ->required(),
                TextInput::make('image_alt')
                    ->label('Texto alternativo')
                    ->maxLength(255)
                    ->required(),
                Repeater::make('items')
                    ->label('Productos destacados')
                    ->relationship()
                    ->schema([
                        Select::make('product_id')
                            ->label('Producto')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('x')->label('Posición horizontal (%)')->numeric()->minValue(0)->maxValue(100)->required(),
                        TextInput::make('y')->label('Posición vertical (%)')->numeric()->minValue(0)->maxValue(100)->required(),
                        TextInput::make('position')->numeric()->default(0)->required(),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
                TextInput::make('position')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_visible')
                    ->required(),
            ]);
    }
}
