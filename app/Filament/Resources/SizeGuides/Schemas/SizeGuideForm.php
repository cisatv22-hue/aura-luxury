<?php

namespace App\Filament\Resources\SizeGuides\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SizeGuideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('unit')
                    ->required()
                    ->default('cm'),
                Textarea::make('rows')
                    ->label('Filas de la tabla (JSON)')
                    ->helperText('Para activar la calculadora añade en cada fila _size, _height_min_cm, _height_max_cm, _weight_min_kg y _weight_max_kg. Usa rangos aprobados por AURA; las claves que empiezan con guion bajo no se muestran en la tabla.')
                    ->required()
                    ->formatStateUsing(fn (?array $state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]')
                    ->dehydrateStateUsing(fn (string $state): array => json_decode($state, true, 512, JSON_THROW_ON_ERROR))
                    ->columnSpanFull(),
                Toggle::make('is_visible')
                    ->required(),
            ]);
    }
}
