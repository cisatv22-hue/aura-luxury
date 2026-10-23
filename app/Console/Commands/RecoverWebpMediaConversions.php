<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\Process\Process;
use Throwable;

class RecoverWebpMediaConversions extends Command
{
    protected $signature = 'aura:recover-webp-conversions
        {--ffmpeg=ffmpeg : Path to the FFmpeg executable}
        {--media= : Only recover the specified media ID}
        {--dry-run : Show the conversions that would be generated without writing files}';

    protected $description = 'Recover local product WebP conversions with FFmpeg when PHP GD lacks WebP support';

    /** @var array<string, array{width: int, height: int}> */
    private const CONVERSIONS = [
        'thumb' => ['width' => 400, 'height' => 500],
        'card' => ['width' => 800, 'height' => 1000],
        'large' => ['width' => 1600, 'height' => 2000],
    ];

    public function handle(): int
    {
        $binaryOption = $this->option('ffmpeg');
        $binary = is_string($binaryOption) && $binaryOption !== ''
            ? $binaryOption
            : 'ffmpeg';
        $version = new Process([$binary, '-version']);
        $version->run();

        if (! $version->isSuccessful()) {
            $this->error("FFmpeg no está disponible: {$binary}");

            return self::FAILURE;
        }

        $mediaQuery = Media::query()
            ->where('model_type', 'App\\Models\\ProductColor')
            ->where('collection_name', 'gallery')
            ->orderBy('id');
        $mediaOption = $this->option('media');
        if (is_string($mediaOption) && $mediaOption !== '') {
            $mediaQuery->whereKey((int) $mediaOption);
        }
        $mediaItems = $mediaQuery->get();

        $generated = 0;
        $skipped = 0;
        $failed = 0;
        $dryRun = (bool) $this->option('dry-run');

        foreach ($mediaItems as $media) {
            if ($media->disk !== 'public' || $media->conversions_disk !== 'public') {
                $skipped++;

                continue;
            }

            if (strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION)) !== 'webp') {
                $skipped++;

                continue;
            }

            try {
                $source = $media->getPath();
            } catch (Throwable $exception) {
                $this->warn("Media {$media->id}: no se pudo resolver el original ({$exception->getMessage()})");
                $failed += count(self::CONVERSIONS);

                continue;
            }

            if (! is_file($source)) {
                $this->warn("Media {$media->id}: falta el original {$source}");
                $failed += count(self::CONVERSIONS);

                continue;
            }

            foreach (self::CONVERSIONS as $name => $dimensions) {
                try {
                    $target = $media->getPath($name);
                } catch (Throwable $exception) {
                    $this->warn("Media {$media->id} / {$name}: no se pudo resolver la ruta ({$exception->getMessage()})");
                    $failed++;

                    continue;
                }

                if ($media->hasGeneratedConversion($name) && $this->isWebp($target)) {
                    $skipped++;

                    continue;
                }

                if ($dryRun) {
                    $this->line("Media {$media->id}: {$name} {$dimensions['width']}×{$dimensions['height']}");
                    $generated++;

                    continue;
                }

                if ($this->isWebp($target)) {
                    $media->markAsConversionGenerated($name);
                    $skipped++;

                    continue;
                }

                File::ensureDirectoryExists(dirname($target));
                $filter = sprintf(
                    "scale=w='min(%d,iw)':h='min(%d,ih)':force_original_aspect_ratio=decrease",
                    $dimensions['width'],
                    $dimensions['height'],
                );
                $process = new Process([
                    $binary,
                    '-hide_banner',
                    '-loglevel',
                    'error',
                    '-y',
                    '-i',
                    $source,
                    '-vf',
                    $filter,
                    '-c:v',
                    'libwebp',
                    '-quality',
                    '80',
                    '-compression_level',
                    '6',
                    $target,
                ]);
                $process->setTimeout(120);
                $process->run();

                if (! $process->isSuccessful() || ! $this->isWebp($target)) {
                    $detail = trim($process->getErrorOutput()) ?: 'no se generó un WebP válido';
                    $this->warn("Media {$media->id} / {$name}: {$detail}");
                    $failed++;

                    continue;
                }

                $media->markAsConversionGenerated($name);
                $generated++;
            }
        }

        $this->newLine();
        $this->info("Conversiones generadas: {$generated}");
        $this->line("Omitidas: {$skipped}");
        $this->line("Fallidas: {$failed}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @phpstan-impure */
    private function isWebp(string $path): bool
    {
        if (! is_file($path) || filesize($path) === 0) {
            return false;
        }

        return (new \finfo(FILEINFO_MIME_TYPE))->file($path) === 'image/webp';
    }
}
