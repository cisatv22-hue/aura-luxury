<?php

use App\Actions\SyncProductColorGallery;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeGalleryManagerColor(): ProductColor
{
    $category = Category::create(['name' => 'Categoría de galería', 'slug' => 'categoria-galeria-manager']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Producto de galería',
        'slug' => 'producto-galeria-manager',
        'description' => 'Prueba de administración de galería',
        'status' => Product::STATUS_DRAFT,
        'base_price_cents' => 10_000,
    ]);

    return ProductColor::create(['product_id' => $product->id, 'name' => 'Negro']);
}

it('adds gallery photos in order with their own alternative text', function () {
    Storage::fake('public');
    Queue::fake();
    $color = makeGalleryManagerColor();
    $source = base_path('tests/Fixtures/products/model_negro.webp');
    $image = file_get_contents($source);
    expect($image)->not->toBeFalse();
    Storage::disk('public')->put('product-gallery/front.webp', $image);
    Storage::disk('public')->put('product-gallery/back.webp', $image);

    app(SyncProductColorGallery::class)->handle($color, [
        ['file' => 'product-gallery/front.webp', 'alt' => 'Vista frontal de la prenda negra'],
        ['file' => 'product-gallery/back.webp', 'alt' => 'Vista posterior de la prenda negra'],
    ]);

    $gallery = $color->getMedia('gallery');
    expect($gallery)->toHaveCount(2)
        ->and($gallery[0]->getCustomProperty('alt'))->toBe('Vista frontal de la prenda negra')
        ->and($gallery[0]->order_column)->toBe(1)
        ->and($gallery[1]->getCustomProperty('alt'))->toBe('Vista posterior de la prenda negra')
        ->and($gallery[1]->order_column)->toBe(2)
        ->and(Storage::disk('public')->exists('product-gallery/front.webp'))->toBeFalse()
        ->and(Storage::disk('public')->exists('product-gallery/back.webp'))->toBeFalse();
});

it('reorders existing gallery photos, updates alt text, and removes deleted photos', function () {
    Storage::fake('public');
    Queue::fake();
    $color = makeGalleryManagerColor();
    $image = file_get_contents(base_path('tests/Fixtures/products/model_negro.webp'));
    Storage::disk('public')->put('product-gallery/first.webp', $image);
    Storage::disk('public')->put('product-gallery/second.webp', $image);
    Storage::disk('public')->put('product-gallery/third.webp', $image);
    $manager = app(SyncProductColorGallery::class);
    $manager->handle($color, [
        ['file' => 'product-gallery/first.webp', 'alt' => 'Primera vista'],
        ['file' => 'product-gallery/second.webp', 'alt' => 'Segunda vista'],
        ['file' => 'product-gallery/third.webp', 'alt' => 'Tercera vista'],
    ]);

    $before = $color->getMedia('gallery')->keyBy(fn (Media $media): string => $media->getCustomProperty('alt'));
    $firstPath = $before->get('Primera vista')->getPathRelativeToRoot();
    $secondPath = $before->get('Segunda vista')->getPathRelativeToRoot();
    $deletedId = $before->get('Tercera vista')->id;

    $manager->handle($color, [
        ['file' => $secondPath, 'alt' => 'Vista posterior actualizada'],
        ['file' => $firstPath, 'alt' => 'Vista frontal actualizada'],
    ]);

    $after = $color->getMedia('gallery');
    expect($after)->toHaveCount(2)
        ->and($after[0]->getCustomProperty('alt'))->toBe('Vista posterior actualizada')
        ->and($after[0]->order_column)->toBe(1)
        ->and($after[1]->getCustomProperty('alt'))->toBe('Vista frontal actualizada')
        ->and($after[1]->order_column)->toBe(2)
        ->and(Media::query()->whereKey($deletedId)->exists())->toBeFalse();
});

it('rejects files outside the gallery upload directory', function () {
    Storage::fake('public');
    Queue::fake();
    $color = makeGalleryManagerColor();
    Storage::disk('public')->put('product-colors/unrelated.webp', file_get_contents(base_path('tests/Fixtures/products/model_negro.webp')));

    expect(fn () => app(SyncProductColorGallery::class)->handle($color, [
        ['file' => 'product-colors/unrelated.webp', 'alt' => 'Foto válida'],
    ]))->toThrow(ValidationException::class);
});
