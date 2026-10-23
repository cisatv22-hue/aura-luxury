<?php

namespace App\Filament\Resources\Reviews\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label('Producto')->searchable(),
                TextColumn::make('customer.name')->label('Cliente')->searchable(),
                TextColumn::make('rating')->label('Calificación')->sortable(),
                TextColumn::make('title')->label('Título')->searchable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('created_at')->label('Recibida')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
