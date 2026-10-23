<?php

namespace App\Filament\Resources\SizeGuides\Pages;

use App\Filament\Resources\SizeGuides\SizeGuideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSizeGuides extends ListRecords
{
    protected static string $resource = SizeGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
