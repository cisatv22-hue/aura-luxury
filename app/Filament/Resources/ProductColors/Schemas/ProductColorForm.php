<?php

namespace App\Filament\Resources\ProductColors\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductColorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('hex'),
                FileUpload::make('image_path')
                    ->label('Foto de respaldo del color')
                    ->image()
                    ->disk('public')
                    ->directory('product-colors')
                    ->visibility('public')
                    ->helperText('La galería principal se administra abajo; esta foto se usa como respaldo.'),
                Textarea::make('image_alt')
                    ->label('Texto alternativo de la foto')
                    ->rows(2)
                    ->required(fn (Get $get): bool => filled($get('image_path')))
                    ->maxLength(160),
                Repeater::make('gallery')
                    ->label('Galería de fotos')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Foto')
                            ->image()
                            ->disk('public')
                            ->directory('product-gallery')
                            ->visibility('public')
                            ->required()
                            ->maxSize(10240),
                        TextInput::make('alt')
                            ->label('Texto alternativo')
                            ->required()
                            ->maxLength(160),
                    ])
                    ->columns(2)
                    ->reorderable()
                    ->collapsible()
                    ->defaultItems(0)
                    ->columnSpanFull()
                    ->helperText('Agrega una foto por elemento. Arrastra para definir el orden; cada foto requiere texto alternativo.'),
                TextInput::make('position')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
