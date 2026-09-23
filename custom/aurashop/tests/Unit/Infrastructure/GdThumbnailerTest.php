<?php

namespace AuraShop\Tests\Unit\Infrastructure;

use AuraShop\Infrastructure\Image\GdThumbnailer;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('gd')]
final class GdThumbnailerTest extends TestCase
{
	private string $dir;

	protected function setUp(): void
	{
		$this->dir = sys_get_temp_dir().'/aurashop-thumbs-'.bin2hex(random_bytes(4));
		mkdir($this->dir.'/src', 0777, true);
	}

	protected function tearDown(): void
	{
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($files as $file) {
			$file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
		}
		rmdir($this->dir);
	}

	public function testResizesKeepingAspectRatioAndReusesCache(): void
	{
		$source = $this->jpeg(1200, 1500);
		$thumbnailer = new GdThumbnailer($this->dir.'/cache');

		$first = $thumbnailer->fit($source, 'REF/photo.jpg', 720, 900);
		$this->assertNotNull($first);
		$this->assertSame(array(720, 900), array_slice(getimagesize($first), 0, 2));

		$mtime = filemtime($first);
		$this->assertSame($first, $thumbnailer->fit($source, 'REF/photo.jpg', 720, 900));
		$this->assertSame($mtime, filemtime($first));
	}

	public function testSmallSourcesAreServedAsIs(): void
	{
		$source = $this->jpeg(400, 500);

		$this->assertSame($source, (new GdThumbnailer($this->dir.'/cache'))->fit($source, 'REF/small.jpg', 720, 900));
	}

	public function testSameNameWithDifferentExtensionDoesNotCollide(): void
	{
		$thumbnailer = new GdThumbnailer($this->dir.'/cache');

		$jpg = $thumbnailer->fit($this->jpeg(1200, 1500, 'photo.jpg'), 'REF/photo.jpg', 720, 900);
		$jpeg = $thumbnailer->fit($this->jpeg(1500, 1200, 'photo.jpeg'), 'REF/photo.jpeg', 720, 900);

		$this->assertNotSame($jpg, $jpeg);
	}

	public function testRefusesToDecodeHugeImages(): void
	{
		// Only the PNG header is written: getimagesize() reads 10000x10000 without decoding anything.
		$source = $this->dir.'/src/huge.png';
		file_put_contents($source, "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.pack('NN', 10000, 10000)."\x08\x02\x00\x00\x00".pack('N', 0));

		$this->assertNull((new GdThumbnailer($this->dir.'/cache'))->fit($source, 'REF/huge.png', 720, 900));
	}

	private function jpeg(int $width, int $height, string $name = 'photo.jpg'): string
	{
		$path = $this->dir.'/src/'.$name;
		$image = imagecreatetruecolor($width, $height);
		imagejpeg($image, $path);
		imagedestroy($image);

		return $path;
	}
}
