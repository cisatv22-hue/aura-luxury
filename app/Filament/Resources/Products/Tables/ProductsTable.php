<?php

namespace App\Filament\Resources\Products\Tables;

use App\Actions\DuplicateProduct;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('brand_line')
                    ->searchable(),
                TextColumn::make('material')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Product::STATUS_ACTIVE => 'Publicado',
                        Product::STATUS_ARCHIVED => 'Archivado',
                        default => 'Borrador',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Product::STATUS_ACTIVE => 'success',
                        Product::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('base_price_cents')
                    ->label('Precio base')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : '$'.number_format($state / 100, 2).' MXN')
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->boolean(),
                TextColumn::make('seo_title')
                    ->searchable(),
                TextColumn::make('seo_description')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('size_guide_id')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        Product::STATUS_DRAFT => 'Borrador',
                        Product::STATUS_ACTIVE => 'Publicado',
                        Product::STATUS_ARCHIVED => 'Archivado',
                    ]),
                SelectFilter::make('category')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('duplicate')
                    ->label('Duplicar')
                    ->requiresConfirmation()
                    ->action(fn (Product $record) => app(DuplicateProduct::class)->handle($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
