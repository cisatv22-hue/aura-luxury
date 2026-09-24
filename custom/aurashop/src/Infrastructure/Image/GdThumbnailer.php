<?php

namespace AuraShop\Infrastructure\Image;

/**
 * Resizes images into the module's own cache (documents/aurashop/cache/images), keeping aspect ratio.
 * A cached file is rebuilt when the source is newer.
 */
final class GdThumbnailer
{
	private const JPEG_QUALITY = 82;

	/**
	 * GD decodes the whole bitmap at ~4 bytes per pixel: 25 MP is ~100 MB, the edge of a 128M memory_limit.
	 * Bigger photos (48-50 MP phone modes) are not resized; the caller serves the original, streamed from disk.
	 */
	public const MAX_SOURCE_PIXELS = 25_000_000;

	public function __construct(private readonly string $cacheDir)
	{
	}

	public function fit(string $source, string $cacheKey, int $maxWidth, int $maxHeight): ?string
	{
		$extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
		$outputExtension = in_array($extension, array('png', 'gif'), true) ? 'png' : 'jpg';
		$target = rtrim($this->cacheDir, '/').'/'.$cacheKey.'_'.$maxWidth.'x'.$maxHeight.'.'.$outputExtension;

		if (is_readable($target) && filemtime($target) >= filemtime($source)) {
			return $target;
		}

		$info = @getimagesize($source);
		if ($info === false) {
			return null;
		}
		[$width, $height] = $info;
		if ($width <= $maxWidth && $height <= $maxHeight) {
			return $source;
		}
		if ($width * $height > self::MAX_SOURCE_PIXELS) {
			return null;
		}

		$image = match ($info[2]) {
			IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
			IMAGETYPE_PNG => @imagecreatefrompng($source),
			IMAGETYPE_GIF => @imagecreatefromgif($source),
			IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
			default => false,
		};
		if ($image === false) {
			return null;
		}

		$ratio = min($maxWidth / $width, $maxHeight / $height);
		$newWidth = max(1, (int) round($width * $ratio));
		$newHeight = max(1, (int) round($height * $ratio));
		$resized = imagecreatetruecolor($newWidth, $newHeight);
		if ($outputExtension === 'png') {
			imagealphablending($resized, false);
			imagesavealpha($resized, true);
		}
		imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

		$dir = dirname($target);
		if (!is_dir($dir) && !@mkdir($dir, 0770, true) && !is_dir($dir)) {
			return null;
		}
		$tmp = $target.'.'.getmypid().'.tmp';
		$ok = $outputExtension === 'png' ? imagepng($resized, $tmp, 6) : imagejpeg($resized, $tmp, self::JPEG_QUALITY);
		imagedestroy($image);
		imagedestroy($resized);

		// Atomic replace so concurrent requests never serve a half-written file.
		return $ok && @rename($tmp, $target) ? $target : null;
	}
}
