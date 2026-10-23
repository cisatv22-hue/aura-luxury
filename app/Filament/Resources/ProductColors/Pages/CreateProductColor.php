<?php

namespace App\Filament\Resources\ProductColors\Pages;

use App\Actions\SyncProductColorGallery;
use App\Filament\Resources\ProductColors\ProductColorResource;
use App\Models\ProductColor;
use Filament\Resources\Pages\CreateRecord;

class CreateProductColor extends CreateRecord
{
    protected static string $resource = ProductColorResource::class;

    /** @var array<int, array{file?: mixed, alt?: mixed}> */
    private array $gallery = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->gallery = is_array($data['gallery'] ?? null) ? $data['gallery'] : [];
        unset($data['gallery']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var ProductColor $color */
        $color = $this->record;
        app(SyncProductColorGallery::class)->handle($color, $this->gallery);
    }
}
