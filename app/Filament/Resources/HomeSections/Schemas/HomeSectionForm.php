<?php

namespace App\Filament\Resources\HomeSections\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HomeSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Select::make('source_type')
                    ->options(['manual' => 'Selección manual', 'new' => 'Nuevos', 'best_selling' => 'Más vendidos', 'low_stock' => 'Pocas existencias', 'collection' => 'Colección', 'on_sale' => 'En oferta'])
                    ->required(),
                Textarea::make('source_config')
                    ->label('Configuración JSON: colección {"slug":"essentials"}, selección manual {"product_ids":[1,2]}')
                    ->formatStateUsing(fn (?array $state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}')
                    ->dehydrateStateUsing(fn (string $state): array => json_decode($state, true, 512, JSON_THROW_ON_ERROR))
                    ->columnSpanFull(),
                TextInput::make('limit')
                    ->required()
                    ->numeric()
                    ->default(8),
                TextInput::make('position')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_visible')
                    ->required(),
            ]);
    }
}
