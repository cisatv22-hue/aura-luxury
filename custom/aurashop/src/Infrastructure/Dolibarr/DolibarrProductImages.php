<?php

namespace AuraShop\Infrastructure\Dolibarr;

use AuraShop\Application\Port\ImageSize;
use AuraShop\Application\Port\ProductImages;
use AuraShop\Infrastructure\Image\GdThumbnailer;

/**
 * Reads the photos Dolibarr stores in documents/produit/{REF}/ (its thumbs live in thumbs/{name}_small.ext).
 */
final class DolibarrProductImages implements ProductImages
{
	private const EXTENSIONS = 'jpg|jpeg|png|webp|gif';

	/** Dolibarr's own thumbs (480x270) are too small for product cards. */
	private const CARD_WIDTH = 720;
	private const CARD_HEIGHT = 900;

	/** @var array<string, list<string>> */
	private array $cache = array();

	public function __construct(
		private readonly \DoliDB $db,
		private readonly string $productsDir,
		private readonly GdThumbnailer $thumbnailer
	) {
	}

	public function list(string $productRef): array
	{
		if (isset($this->cache[$productRef])) {
			return $this->cache[$productRef];
		}
		$dir = $this->dirFor($productRef);
		$files = array();
		if (is_dir($dir)) {
			foreach (scandir($dir) ?: array() as $file) {
				if ($file[0] !== '.' && is_file($dir.$file) && preg_match('/\.('.self::EXTENSIONS.')$/i', $file)) {
					$files[] = $file;
				}
			}
			natcasesort($files);
		}

		return $this->cache[$productRef] = array_values($files);
	}

	public function resolve(string $productRef, string $fileName, ImageSize $size): ?string
	{
		if ($fileName !== basename($fileName) || !in_array($fileName, $this->list($productRef), true)) {
			return null;
		}
		if (!$this->isPublicProduct($productRef)) {
			return null;
		}

		$full = $this->dirFor($productRef).$fileName;
		if (!is_readable($full)) {
			return null;
		}

		switch ($size) {
			case ImageSize::Full:
				return $full;
			case ImageSize::Card:
				$key = dol_sanitizeFileName($productRef).'/'.pathinfo($fileName, PATHINFO_FILENAME);

				return $this->thumbnailer->fit($full, $key, self::CARD_WIDTH, self::CARD_HEIGHT) ?? $full;
			default:
				$info = pathinfo($fileName);
				$thumb = $this->dirFor($productRef).'thumbs/'.$info['filename'].'_'.$size->value.'.'.$info['extension'];

				return is_readable($thumb) ? $thumb : $full;
		}
	}

	private function dirFor(string $productRef): string
	{
		return rtrim($this->productsDir, '/').'/'.dol_sanitizeFileName($productRef).'/';
	}

	private function isPublicProduct(string $productRef): bool
	{
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product";
		$sql .= " WHERE ref = '".$this->db->escape($productRef)."'";
		$sql .= " AND tosell = 1 AND entity IN (".getEntity('product').")";
		$resql = $this->db->query($sql);

		return $resql && $this->db->num_rows($resql) > 0;
	}
}
