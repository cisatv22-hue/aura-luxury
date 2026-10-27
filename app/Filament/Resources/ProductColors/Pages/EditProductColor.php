<?php

namespace App\Filament\Resources\ProductColors\Pages;

use App\Actions\SyncProductColorGallery;
use App\Filament\Resources\ProductColors\ProductColorResource;
use App\Models\ProductColor;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EditProductColor extends EditRecord
{
    protected static string $resource = ProductColorResource::class;

    /** @var array<int, array{file?: mixed, alt?: mixed}> */
    private array $gallery = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var ProductColor $color */
        $color = $this->record;
        $data['gallery'] = $color->getMedia('gallery')
            ->map(fn (Media $media): array => [
                'file' => $media->getPathRelativeToRoot(),
                'alt' => (string) ($media->getCustomProperty('alt') ?? ''),
            ])
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->gallery = is_array($data['gallery'] ?? null) ? $data['gallery'] : [];
        unset($data['gallery']);

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var ProductColor $color */
        $color = $this->record;
        app(SyncProductColorGallery::class)->handle($color, $this->gallery);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
