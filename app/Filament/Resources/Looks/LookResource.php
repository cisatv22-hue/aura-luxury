<?php

namespace App\Filament\Resources\Looks;

use App\Filament\Resources\Concerns\AuthorizesByPermission;
use App\Filament\Resources\Looks\Pages\CreateLook;
use App\Filament\Resources\Looks\Pages\EditLook;
use App\Filament\Resources\Looks\Pages\ListLooks;
use App\Filament\Resources\Looks\Schemas\LookForm;
use App\Filament\Resources\Looks\Tables\LooksTable;
use App\Models\Look;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LookResource extends Resource
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'content';
    }

    protected static ?string $model = Look::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return LookForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LooksTable::configure($table);
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
            'index' => ListLooks::route('/'),
            'create' => CreateLook::route('/create'),
            'edit' => EditLook::route('/{record}/edit'),
        ];
    }
}
