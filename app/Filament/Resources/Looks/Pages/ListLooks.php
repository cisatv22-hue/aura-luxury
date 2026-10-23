<?php

namespace App\Filament\Resources\Looks\Pages;

use App\Filament\Resources\Looks\LookResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLooks extends ListRecords
{
    protected static string $resource = LookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
