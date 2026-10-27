<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('imports secondary catalog photos without duplicating them or publishing products', function () {
    Storage::fake('public');
    Queue::fake();

    $directory = storage_path('framework/testing/aura-legacy-import');
    $assetDirectory = $directory.DIRECTORY_SEPARATOR.'products';
    File::deleteDirectory($directory);
    File::ensureDirectoryExists($assetDirectory);

    $records = [
        ['id' => 100, 'name' => 'Sudadera Essentials', 'category' => 'hoodies', 'price' => 900, 'desc' => 'Borrador Essentials', 'options' => []],
        ['id' => 9, 'name' => 'Cadena de Plata Ley .925', 'category' => 'chains', 'price' => 500, 'desc' => 'Borrador de cadena', 'options' => ['Único']],
        ['id' => 11, 'name' => 'Kit Essentials & Pulsera de Plata', 'category' => 'combos', 'price' => 1100, 'desc' => 'Borrador del kit', 'options' => ['Único']],
    ];
    $sourcePath = $directory.DIRECTORY_SEPARATOR.'main.js';
    file_put_contents($sourcePath, 'const products = '.json_encode($records, JSON_THROW_ON_ERROR).';');

    $assetNames = [
        'model_blanco.webp', 'model_negro.webp', 'model_crema.webp', 'model_gris.webp', 'model_verde.webp', 'model_total_black.webp',
        'hero_essentials.webp', 'hero_essentials_black.webp', 'hero_essentials_beige.webp', 'hero_essentials_grey.webp', 'hero_essentials_green.webp', 'hero_essentials_totalblack.webp',
        'p9.webp', 'p9dark.webp', 'p11.webp', 'combo_3pieces.webp', 'combo_beige.webp', 'combo_black.webp', 'combo_green.webp', 'combo_white.webp',
    ];

    foreach ($assetNames as $assetName) {
        File::copy(base_path('tests/Fixtures/products/model_negro.png'), $assetDirectory.DIRECTORY_SEPARATOR.$assetName);
    }

    try {
        expect(Artisan::call('aura:import-legacy', ['--source' => $sourcePath, '--assets' => $assetDirectory]))
            ->toBe(0);

        $essentials = Product::query()->where('slug', 'sudadera-essentials')->firstOrFail();
        $chain = Product::query()->where('slug', 'cadena-de-plata-ley-925')->firstOrFail();
        $kit = Product::query()->where('slug', 'kit-essentials-pulsera-de-plata')->firstOrFail();

        expect($essentials->status)->toBe(Product::STATUS_DRAFT)
            ->and($essentials->colors()->with('media')->get()->sum(fn ($color) => $color->getMedia('gallery')->count()))->toBe(12)
            ->and($essentials->colors()->where('name', 'NEGRO')->firstOrFail()->getMedia('gallery')->pluck('file_name')->all())
            ->toContain('hero_essentials_black.webp')
            ->and($chain->status)->toBe(Product::STATUS_DRAFT)
            ->and($chain->colors()->firstOrFail()->getMedia('gallery')->pluck('file_name')->all())
            ->toContain('p9dark.webp')
            ->and($kit->status)->toBe(Product::STATUS_DRAFT)
            ->and($kit->colors()->firstOrFail()->getMedia('gallery')->pluck('file_name')->all())
            ->toContain('combo_beige.webp', 'combo_black.webp', 'combo_green.webp', 'combo_white.webp', 'combo_3pieces.webp')
            ->and(Product::query()->where('status', Product::STATUS_ACTIVE)->count())->toBe(0);

        expect(Artisan::call('aura:import-legacy', ['--source' => $sourcePath, '--assets' => $assetDirectory]))
            ->toBe(0)
            ->and(Artisan::output())->toContain('0 imágenes nuevas')
            ->and($essentials->colors()->get()->sum(fn ($color) => $color->fresh()->getMedia('gallery')->count()))->toBe(12)
            ->and($chain->colors()->firstOrFail()->fresh()->getMedia('gallery'))->toHaveCount(2)
            ->and($kit->colors()->firstOrFail()->fresh()->getMedia('gallery'))->toHaveCount(6);
    } finally {
        File::deleteDirectory($directory);
    }
});
