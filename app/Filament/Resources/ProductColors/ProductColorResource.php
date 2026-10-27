<?php

namespace App\Filament\Resources\ProductColors;

use App\Filament\Resources\Concerns\AuthorizesByPermission;
use App\Filament\Resources\ProductColors\Pages\CreateProductColor;
use App\Filament\Resources\ProductColors\Pages\EditProductColor;
use App\Filament\Resources\ProductColors\Pages\ListProductColors;
use App\Filament\Resources\ProductColors\Schemas\ProductColorForm;
use App\Filament\Resources\ProductColors\Tables\ProductColorsTable;
use App\Models\ProductColor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductColorResource extends Resource
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'catalog';
    }

    protected static ?string $model = ProductColor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ProductColorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductColorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductColors::route('/'),
            'create' => CreateProductColor::route('/create'),
            'edit' => EditProductColor::route('/{record}/edit'),
        ];
    }
}
