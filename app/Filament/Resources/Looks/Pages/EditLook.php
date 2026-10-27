<?php

namespace App\Filament\Resources\Looks\Pages;

use App\Filament\Resources\Looks\LookResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLook extends EditRecord
{
    protected static string $resource = LookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
