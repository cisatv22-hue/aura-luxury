<?php

namespace App\Actions;

use App\Models\ProductColor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SyncProductColorGallery
{
    /** @param array<int, array{file?: mixed, alt?: mixed}> $items */
    public function handle(ProductColor $color, array $items): void
    {
        $disk = Storage::disk('public');
        $existing = $color->getMedia('gallery')->keyBy(fn (Media $media): string => $media->getPathRelativeToRoot());
        $keptIds = [];
        $seenPaths = [];

        foreach (array_values($items) as $index => $item) {
            $path = $item['file'] ?? null;
            $alt = $item['alt'] ?? null;

            if (! is_string($path) || $path === '' || ! is_string($alt) || trim($alt) === '' || mb_strlen($alt) > 160) {
                throw ValidationException::withMessages(['data.gallery' => 'Cada foto requiere un archivo y un texto alternativo de hasta 160 caracteres.']);
            }

            if (isset($seenPaths[$path])) {
                throw ValidationException::withMessages(['data.gallery' => 'No agregues la misma foto más de una vez.']);
            }

            $seenPaths[$path] = true;

            if ($existing->has($path)) {
                /** @var Media $media */
                $media = $existing->get($path);
                $media->setCustomProperty('alt', trim($alt));
                $media->order_column = $index + 1;
                $media->save();
                $keptIds[] = $media->id;

                continue;
            }

            if (! str_starts_with($path, 'product-gallery/') || ! $disk->exists($path)) {
                throw ValidationException::withMessages(['data.gallery' => 'Una foto de la galería ya no está disponible. Vuelve a seleccionarla.']);
            }

            $media = $color->addMediaFromDisk($path, 'public')
                ->withCustomProperties(['alt' => trim($alt)])
                ->setOrder($index + 1)
                ->toMediaCollection('gallery', 'public');
            $disk->delete($path);
            $keptIds[] = $media->id;
        }

        $color->unsetRelation('media');
        $color->media()
            ->where('collection_name', 'gallery')
            ->whereNotIn('id', $keptIds ?: [0])
            ->get()
            ->each(fn (Media $media) => $media->delete());
    }
}
