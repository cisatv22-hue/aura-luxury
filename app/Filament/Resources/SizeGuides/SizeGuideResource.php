<?php

namespace App\Filament\Resources\SizeGuides;

use App\Filament\Resources\Concerns\AuthorizesByPermission;
use App\Filament\Resources\SizeGuides\Pages\CreateSizeGuide;
use App\Filament\Resources\SizeGuides\Pages\EditSizeGuide;
use App\Filament\Resources\SizeGuides\Pages\ListSizeGuides;
use App\Filament\Resources\SizeGuides\Schemas\SizeGuideForm;
use App\Filament\Resources\SizeGuides\Tables\SizeGuidesTable;
use App\Models\SizeGuide;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SizeGuideResource extends Resource
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'content';
    }

    protected static ?string $model = SizeGuide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SizeGuideForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SizeGuidesTable::configure($table);
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
            'index' => ListSizeGuides::route('/'),
            'create' => CreateSizeGuide::route('/create'),
            'edit' => EditSizeGuide::route('/{record}/edit'),
        ];
    }
}
