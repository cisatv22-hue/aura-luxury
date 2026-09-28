<?php

namespace AuraShop\Tests\Integration;

use AuraShop\Application\Port\ImageSize;
use AuraShop\Application\Port\ProductImages;
use AuraShop\Domain\Catalog\Availability;
use AuraShop\Domain\Catalog\CatalogQuery;
use AuraShop\Infrastructure\Dolibarr\DolibarrCatalogReader;
use AuraShop\Infrastructure\Dolibarr\DolibarrHtmlSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Runs against the real Dolibarr database (container only): AURASHOP_DOLIBARR=1 php vendor/bin/phpunit
 * Every test works inside a transaction that is rolled back, so no data is left behind.
 */
final class DolibarrCatalogReaderTest extends TestCase
{
	private const PREFIX = 'ZZTEST-AURASHOP-';

	private \DoliDB $db;
	private DolibarrCatalogReader $reader;

	protected function setUp(): void
	{
		if (!defined('DOL_VERSION')) {
			$this->markTestSkipped('Requires Dolibarr: run inside the container with AURASHOP_DOLIBARR=1.');
		}
		global $db;
		$this->db = $db;
		$this->db->begin();

		$noImages = new class () implements ProductImages {
			public function list(string $productRef): array
			{
				return array();
			}

			public function resolve(string $productRef, string $fileName, ImageSize $size): ?string
			{
				return null;
			}
		};
		$this->reader = new DolibarrCatalogReader($this->db, $noImages, new DolibarrHtmlSanitizer(), 'MXN', null);
	}

	protected function tearDown(): void
	{
		if (isset($this->db)) {
			$this->db->rollback();
		}
	}

	public function testProductWhoseVariantsAreAllNotForSaleIsListedWithoutVariants(): void
	{
		$parent = $this->product('HIDDEN', 1450, 0);
		$this->variant($parent, $this->product('HIDDEN-S', 1450, 5, 0));
		$this->variant($parent, $this->product('HIDDEN-M', 1450, 5, 0));

		$summary = $this->listed('HIDDEN');
		$detail = $this->reader->findByRef(self::PREFIX.'HIDDEN');

		$this->assertFalse($summary->hasVariants);
		$this->assertNotNull($detail);
		$this->assertFalse($detail->hasVariants());
		$this->assertSame($detail->availability, $summary->availability);
	}

	public function testOnlyVariantsForSaleCountForPriceAndStock(): void
	{
		$parent = $this->product('MIXED', 1450, 0);
		$this->variant($parent, $this->product('MIXED-S', 1400, 0, 0));
		$this->variant($parent, $this->product('MIXED-M', 1500, 8));

		$summary = $this->listed('MIXED');
		$detail = $this->reader->findByRef(self::PREFIX.'MIXED');

		$this->assertTrue($summary->hasVariants);
		$this->assertSame(150000, $summary->price->amount);
		$this->assertSame(Availability::InStock, $summary->availability);
		$this->assertNotNull($detail);
		$this->assertSame(array(self::PREFIX.'MIXED-M'), array_map(static fn ($v) => $v->ref, $detail->variants));
	}

	public function testVariantChildrenAreNotListedOnTheirOwn(): void
	{
		$parent = $this->product('PARENT', 1000, 0);
		$this->variant($parent, $this->product('PARENT-S', 1000, 3));

		$refs = array_map(
			static fn ($p) => $p->ref,
			$this->reader->search(new CatalogQuery(null, self::PREFIX.'PARENT'), null)->items
		);

		$this->assertSame(array(self::PREFIX.'PARENT'), $refs);
	}

	private function listed(string $ref): \AuraShop\Domain\Catalog\ProductSummary
	{
		foreach ($this->reader->search(new CatalogQuery(null, self::PREFIX.$ref), null)->items as $item) {
			if ($item->ref === self::PREFIX.$ref) {
				return $item;
			}
		}
		$this->fail('Product '.$ref.' not listed');
	}

	private function product(string $ref, float $priceTtc, int $stock, int $toSell = 1): int
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product (ref, label, entity, fk_product_type, tosell, tobuy, price, price_ttc, tva_tx, price_base_type, stock, datec)";
		$sql .= " VALUES ('".$this->db->escape(self::PREFIX.$ref)."', '".$this->db->escape($ref)."', 1, 0, ".$toSell.", 1,";
		$sql .= " ".($priceTtc / 1.16).", ".$priceTtc.", 16, 'TTC', ".$stock.", '".$this->db->idate(dol_now())."')";
		$this->assertTrue((bool) $this->db->query($sql), (string) $this->db->lasterror());

		return (int) $this->db->last_insert_id(MAIN_DB_PREFIX.'product');
	}

	private function variant(int $parentId, int $childId): void
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, entity)";
		$sql .= " VALUES (".$parentId.", ".$childId.", 0, 0, 0, 1)";
		$this->assertTrue((bool) $this->db->query($sql), (string) $this->db->lasterror());
	}
}
