<?php

namespace AuraShop\Tests\Unit\Domain;

use AuraShop\Domain\Catalog\Availability;
use AuraShop\Domain\Catalog\CatalogQuery;
use AuraShop\Domain\Catalog\Category;
use AuraShop\Domain\Catalog\CategoryTree;
use AuraShop\Domain\Catalog\ProductDetail;
use AuraShop\Domain\Catalog\ProductSort;
use AuraShop\Domain\Catalog\Variant;
use AuraShop\Domain\Shared\Money;
use PHPUnit\Framework\TestCase;

final class CatalogTest extends TestCase
{
	public function testQueryClampsPaginationAndTrimsSearch(): void
	{
		$query = new CatalogQuery(0, '   ', ProductSort::Newest, -3, 500);

		$this->assertNull($query->categoryId);
		$this->assertNull($query->search);
		$this->assertSame(1, $query->page);
		$this->assertSame(CatalogQuery::MAX_PER_PAGE, $query->perPage);
	}

	public function testQueryOffset(): void
	{
		$this->assertSame(48, (new CatalogQuery(null, null, ProductSort::Name, 3, 24))->offset());
	}

	public function testUnknownSortFallsBackToNewest(): void
	{
		$this->assertSame(ProductSort::Newest, ProductSort::fromInput('random'));
		$this->assertSame(ProductSort::PriceDesc, ProductSort::fromInput('price_desc'));
	}

	public function testAvailabilityThresholds(): void
	{
		$this->assertSame(Availability::OutOfStock, Availability::fromQuantity(0));
		$this->assertSame(Availability::LowStock, Availability::fromQuantity(3));
		$this->assertSame(Availability::InStock, Availability::fromQuantity(4));
		$this->assertFalse(Availability::OutOfStock->isPurchasable());
	}

	public function testCategoryTreeIncludesDescendants(): void
	{
		$tree = new CategoryTree(array(
			new Category(1, 'Plata .925'),
			new Category(2, 'Cadenas', 1),
			new Category(3, 'Cubanas', 2),
			new Category(4, 'Sudaderas'),
		));

		$this->assertSame(array(1, 2, 3), $tree->withDescendants(1));
		$this->assertSame(array(4), $tree->withDescendants(4));
		$this->assertSame(array(), $tree->withDescendants(99));
	}

	public function testCategoryTreeSurvivesCycles(): void
	{
		$tree = new CategoryTree(array(new Category(1, 'A', 2), new Category(2, 'B', 1)));

		$this->assertSame(array(1, 2), $tree->withDescendants(1));
	}

	public function testPriceFromIsLowestVariantPrice(): void
	{
		$mxn = static fn (string $v) => Money::fromDecimal($v, 'MXN');
		$product = new ProductDetail('H', 'Hoodie', '', '', $mxn('1450'), array(), array(), Availability::InStock, array(), array(
			new Variant('H-S', array('TALLA' => 'S'), $mxn('1400'), Availability::InStock),
			new Variant('H-XL', array('TALLA' => 'XL'), $mxn('1550'), Availability::InStock),
		));

		$this->assertTrue($product->hasVariants());
		$this->assertSame(140000, $product->priceFrom()->amount);
	}
}
