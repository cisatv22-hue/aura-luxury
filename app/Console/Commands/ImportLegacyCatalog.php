<?php

namespace App\Console\Commands;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Services\PriceHistoryRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class ImportLegacyCatalog extends Command
{
    protected $signature = 'aura:import-legacy
        {--source= : Ruta al js/main.js de la tienda actual}
        {--assets= : Ruta a aura-v2-assets/products}
        {--database= : Conexión de base de datos a usar durante la importación}
        {--dry-run : Lee y valida el catálogo sin escribir datos ni archivos}';

    protected $description = 'Importa el catálogo de la tienda anterior como productos en borrador';

    /** @var array<int, array{name: string, color: string}> */
    private const GROUPS = [
        108 => ['name' => 'Short Essentials Fear of God', 'color' => 'Gris'],
        109 => ['name' => 'Short Essentials Fear of God', 'color' => 'Black'],
        110 => ['name' => 'Short Essentials Fear of God', 'color' => 'Grey'],
        301 => ['name' => 'Pants Rompevientos Nike', 'color' => 'Negro'],
        302 => ['name' => 'Pants Rompevientos Nike', 'color' => 'Cream'],
        303 => ['name' => 'Pants Rompevientos Nike', 'color' => 'Gris'],
        401 => ['name' => 'Pants Youngla Skulls', 'color' => 'Black'],
        402 => ['name' => 'Pants Youngla Skulls', 'color' => 'Grey'],
    ];

    /** @var array<string, string> */
    private const CATEGORY_NAMES = [
        'hoodies' => 'Sudaderas',
        'streetwear' => 'Streetwear',
        'pants' => 'Pants',
        'pants_youngla' => 'Pants YoungLA',
        'jackets_afelpadas' => 'Chamarras afelpadas',
        'chains' => 'Plata .925',
        'combos' => 'Kits',
        'caps' => 'Gorras',
        'all' => 'Destacados',
    ];

    /** @var array<string, string> */
    private const ESSENTIALS_IMAGES = [
        'BLANCO' => 'model_blanco.webp',
        'NEGRO' => 'model_negro.webp',
        'CREMA' => 'model_crema.webp',
        'GRIS' => 'model_gris.webp',
        'VERDE' => 'model_verde.webp',
        'TOTAL BLACK' => 'model_total_black.webp',
    ];

    private const ESSENTIALS_GALLERY_IMAGES = [
        'BLANCO' => 'hero_essentials.webp',
        'NEGRO' => 'hero_essentials_black.webp',
        'CREMA' => 'hero_essentials_beige.webp',
        'GRIS' => 'hero_essentials_grey.webp',
        'VERDE' => 'hero_essentials_green.webp',
        'TOTAL BLACK' => 'hero_essentials_totalblack.webp',
    ];

    private const KIT_GALLERY_IMAGES = [
        'combo_3pieces.webp',
        'combo_beige.webp',
        'combo_black.webp',
        'combo_green.webp',
        'combo_white.webp',
    ];

    public function handle(): int
    {
        $connection = $this->option('database');
        if (is_string($connection) && $connection !== '') {
            DB::setDefaultConnection($connection);
        }

        $sourceOption = $this->option('source');
        $assetsOption = $this->option('assets');
        $sourcePath = is_string($sourceOption) && $sourceOption !== '' ? $sourceOption : base_path('../js/main.js');
        $assetDirectory = is_string($assetsOption) && $assetsOption !== '' ? $assetsOption : base_path('../aura-v2-assets/products');

        if (! is_file($sourcePath)) {
            $this->error("No se encontró el catálogo: {$sourcePath}");

            return self::FAILURE;
        }

        if (! is_dir($assetDirectory)) {
            $this->error("No se encontró la carpeta de imágenes WebP: {$assetDirectory}");

            return self::FAILURE;
        }

        try {
            $records = $this->readLegacyProducts($sourcePath);
        } catch (JsonException|RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $groups = $this->groupProducts($records);
        $this->info(sprintf(
            'Catálogo válido: %d registros anteriores, %d productos agrupados y %d variantes potenciales.',
            count($records),
            count($groups),
            array_sum(array_map(fn (array $group) => $this->variantCount($group), $groups)),
        ));

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $categoryModels = $this->upsertCategories($records);
        $essentials = ProductCollection::firstOrCreate(
            ['slug' => 'essentials'],
            ['name' => 'Essentials', 'description' => 'Piezas esenciales de AURA', 'is_visible' => true],
        );
        $createdProducts = 0;
        $createdVariants = 0;
        $copiedImages = 0;

        foreach ($groups as $group) {
            $product = Product::firstOrCreate(
                ['slug' => Str::slug($group['name'])],
                [
                    'category_id' => $categoryModels[$group['category']]['id'] ?? null,
                    'name' => $group['name'],
                    'brand_line' => $this->brandLine($group['name']),
                    'description' => $group['description'],
                    'material' => null,
                    'status' => Product::STATUS_DRAFT,
                    'base_price_cents' => $group['price'] * 100,
                    'is_featured' => false,
                ],
            );
            $createdProducts += (int) $product->wasRecentlyCreated;
            app(PriceHistoryRecorder::class)->recordProduct($product);

            if ($this->isEssentials($group['name'])) {
                $essentials->products()->syncWithoutDetaching([$product->id]);
            }

            foreach ($group['colors'] as $colorIndex => $color) {
                $imageName = $this->imageName($group, $color);
                $productColor = ProductColor::firstOrCreate(
                    ['product_id' => $product->id, 'name' => $color],
                    [
                        'image_alt' => "{$group['name']} color {$color}",
                        'position' => $colorIndex,
                    ],
                );
                $copiedImages += (int) $this->attachImage($productColor, $assetDirectory, $imageName);

                foreach ($this->additionalImageNames($group, $color) as $additionalImage) {
                    $copiedImages += (int) $this->attachAdditionalImage($productColor, $assetDirectory, $additionalImage);
                }

                foreach ($group['sizes'] as $size) {
                    $sku = Str::upper(Str::slug("AURA {$product->slug} {$color} {$size}"));
                    $variant = ProductVariant::firstOrCreate(
                        ['sku' => $sku],
                        [
                            'product_id' => $product->id,
                            'product_color_id' => $productColor->id,
                            'size' => $size,
                            'stock' => 0,
                            'low_stock_at' => 3,
                            'is_active' => true,
                        ],
                    );
                    $createdVariants += (int) $variant->wasRecentlyCreated;
                }
            }
        }

        $kit = Product::query()->where('slug', Str::slug('Kit Essentials & Pulsera de Plata'))->first();
        $bundleItems = Product::query()->whereIn('name', [
            'Sudadera Essentials',
            'Short Essentials Fear of God',
            'Pulsera de Plata Ley .925',
        ])->get()->keyBy('name');

        if ($kit && $bundleItems->count() === 3) {
            $bundle = Bundle::query()->firstOrCreate(
                ['slug' => 'kit-essentials-pulsera-plata'],
                ['product_id' => $kit->id, 'name' => 'Kit Essentials & Pulsera de Plata', 'price_cents' => 110_000, 'is_active' => true],
            );

            foreach ($bundleItems as $itemProduct) {
                $bundle->items()->firstOrCreate(['product_id' => $itemProduct->id], ['qty' => 1]);
            }
        }

        $this->info("Importación lista: {$createdProducts} productos, {$createdVariants} variantes y {$copiedImages} imágenes nuevas.");
        $this->warn('Todo se importó como borrador y el inventario inicia en cero para evitar publicar existencias no verificadas.');

        return self::SUCCESS;
    }

    /** @return list<array<string, mixed>> */
    private function readLegacyProducts(string $path): array
    {
        $source = file_get_contents($path);

        if ($source === false) {
            throw new RuntimeException("No se pudo leer el catálogo: {$path}");
        }

        $declaration = strpos($source, 'const products');
        $start = $declaration === false ? false : strpos($source, '[', $declaration);

        if ($start === false) {
            throw new RuntimeException('No se encontró el arreglo const products en el archivo de origen.');
        }

        $depth = 0;
        $inString = false;
        $escaped = false;
        $end = null;

        for ($index = $start, $length = strlen($source); $index < $length; $index++) {
            $character = $source[$index];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($character === '"') {
                $inString = true;
            } elseif ($character === '[') {
                $depth++;
            } elseif ($character === ']' && --$depth === 0) {
                $end = $index;
                break;
            }
        }

        if ($end === null) {
            throw new RuntimeException('El arreglo products está incompleto.');
        }

        $products = json_decode(substr($source, $start, $end - $start + 1), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($products) || ! array_is_list($products) || $products === []) {
            throw new RuntimeException('El catálogo de origen está vacío o no tiene el formato esperado.');
        }

        $records = [];
        foreach ($products as $product) {
            if (! is_array($product)) {
                throw new RuntimeException('El catálogo contiene un producto con formato inválido.');
            }
            $records[] = $product;
        }

        return $records;
    }

    /** @param list<array<string, mixed>> $records
     * @return array<string, array{name: string, category: string, price: int, description: mixed, source_ids: list<int>, records: list<array<string, mixed>>, colors: list<string>, sizes: list<string> }>
     */
    private function groupProducts(array $records): array
    {
        $groups = [];

        foreach ($records as $record) {
            $id = (int) ($record['id'] ?? 0);
            $mapping = self::GROUPS[$id] ?? null;
            $name = $mapping['name'] ?? (string) ($record['name'] ?? "Producto {$id}");
            $slug = Str::slug($name);
            $category = (string) ($record['category'] ?? 'all');

            if (! isset($groups[$slug])) {
                $groups[$slug] = [
                    'name' => $name,
                    'category' => $category,
                    'price' => (int) ($record['price'] ?? 0),
                    'description' => $record['desc'] ?? null,
                    'source_ids' => [],
                    'records' => [],
                    'colors' => [],
                    'sizes' => [],
                ];
            }

            $groups[$slug]['source_ids'][] = $id;
            $groups[$slug]['records'][] = $record;
            $colors = $id === 100
                ? array_keys(self::ESSENTIALS_IMAGES)
                : [$mapping['color'] ?? 'Único'];
            $groups[$slug]['colors'] = array_merge($groups[$slug]['colors'], $colors);

            $sizes = $id === 100 || $id === 11
                ? ['S', 'M', 'L', 'XL']
                : $this->normalizeSizes($record['options'] ?? []);

            foreach ($sizes as $size) {
                if (! in_array($size, $groups[$slug]['sizes'], true)) {
                    $groups[$slug]['sizes'][] = $size;
                }
            }
        }

        foreach ($groups as &$group) {
            $group['colors'] = array_values(array_unique($group['colors']));
        }
        unset($group);

        return $groups;
    }

    /** @param array<int, string> $options
     * @return array<int, string>
     */
    private function normalizeSizes(array $options): array
    {
        return array_values(array_unique(array_map(function (string $option): string {
            if (preg_match('/\(([^)]+)\)/', $option, $match)) {
                return Str::upper(trim($match[1]));
            }

            return Str::upper(trim($option));
        }, array_filter($options, fn (string $option) => $option !== 'Ver Opciones'))));
    }

    /** @param array<string, mixed> $group */
    private function variantCount(array $group): int
    {
        return count($group['colors']) * count($group['sizes']);
    }

    /** @param array<int, array<string, mixed>> $records
     * @return array<string, Category>
     */
    private function upsertCategories(array $records): array
    {
        $categories = [];

        foreach (array_unique(array_map(fn (array $record) => (string) ($record['category'] ?? 'all'), $records)) as $slug) {
            $categories[$slug] = Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => self::CATEGORY_NAMES[$slug] ?? Str::headline($slug), 'position' => count($categories)],
            );
        }

        return $categories;
    }

    /** @param array{name: string, records: list<array<string, mixed>>, source_ids: list<int>} $group */
    private function imageName(array $group, string $color): string
    {
        if (in_array($group['name'], ['Sudadera Essentials'], true)) {
            return self::ESSENTIALS_IMAGES[$color] ?? 'p100.webp';
        }

        $record = null;
        foreach ($group['records'] as $item) {
            if ((self::GROUPS[(int) ($item['id'] ?? 0)]['color'] ?? 'Único') === $color) {
                $record = $item;
                break;
            }
        }
        $id = (int) ($record['id'] ?? $group['source_ids'][0]);

        return "p{$id}.webp";
    }

    /**
     * @param  array{name: string}  $group
     * @return list<string>
     */
    private function additionalImageNames(array $group, string $color): array
    {
        if ($group['name'] === 'Sudadera Essentials') {
            return isset(self::ESSENTIALS_GALLERY_IMAGES[$color])
                ? [self::ESSENTIALS_GALLERY_IMAGES[$color]]
                : [];
        }

        if ($group['name'] === 'Kit Essentials & Pulsera de Plata') {
            return self::KIT_GALLERY_IMAGES;
        }

        if ($group['name'] === 'Cadena de Plata Ley .925') {
            return ['p9dark.webp'];
        }

        return [];
    }

    private function attachImage(ProductColor $color, string $assetDirectory, string $filename): bool
    {
        $source = $assetDirectory.DIRECTORY_SEPARATOR.$filename;

        if ($color->hasMedia('gallery') || ! is_file($source)) {
            return false;
        }

        $color->addMedia($source)
            ->preservingOriginal()
            ->usingFileName($filename)
            ->withCustomProperties(['alt' => $color->image_alt])
            ->toMediaCollection('gallery', 'public');

        return true;
    }

    private function attachAdditionalImage(ProductColor $color, string $assetDirectory, string $filename): bool
    {
        $source = $assetDirectory.DIRECTORY_SEPARATOR.$filename;

        if (! is_file($source) || $color->getMedia('gallery')->contains('file_name', $filename)) {
            return false;
        }

        $productName = $color->product?->name;
        $alt = match (true) {
            $productName === 'Kit Essentials & Pulsera de Plata' => $this->kitImageAlt($filename),
            $productName === 'Cadena de Plata Ley .925' => 'Cadena de plata .925 sobre fondo negro',
            default => "Sudadera Essentials color {$color->name}, vista posterior",
        };

        $color->addMedia($source)
            ->preservingOriginal()
            ->usingFileName($filename)
            ->withCustomProperties(['alt' => $alt])
            ->toMediaCollection('gallery', 'public');

        return true;
    }

    private function kitImageAlt(string $filename): string
    {
        $color = match ($filename) {
            'combo_beige.webp' => 'crema',
            'combo_black.webp' => 'negro',
            'combo_green.webp' => 'verde',
            'combo_white.webp' => 'blanco',
            default => 'gris',
        };

        return "Kit Essentials con pulsera de plata, conjunto color {$color}";
    }

    private function brandLine(string $name): ?string
    {
        foreach (['Fear of God', 'Nike', 'Youngla', 'G-Style', 'F-Style'] as $brand) {
            if (Str::contains(Str::lower($name), Str::lower($brand))) {
                return $brand;
            }
        }

        return null;
    }

    private function isEssentials(string $name): bool
    {
        return Str::contains(Str::lower($name), 'essentials')
            && ! Str::startsWith(Str::lower($name), 'kit');
    }
}
