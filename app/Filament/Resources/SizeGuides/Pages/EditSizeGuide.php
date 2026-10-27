<?php

namespace App\Filament\Resources\SizeGuides\Pages;

use App\Filament\Resources\SizeGuides\SizeGuideResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSizeGuide extends EditRecord
{
    protected static string $resource = SizeGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
