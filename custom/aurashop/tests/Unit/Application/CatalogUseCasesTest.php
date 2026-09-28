<?php

namespace AuraShop\Tests\Unit\Application;

use AuraShop\Application\Port\CatalogReader;
use AuraShop\Application\UseCase\Catalog\GetProduct;
use AuraShop\Application\UseCase\Catalog\ListProducts;
use AuraShop\Domain\Catalog\Availability;
use AuraShop\Domain\Catalog\CatalogQuery;
use AuraShop\Domain\Catalog\Category;
use AuraShop\Domain\Catalog\ProductDetail;
use AuraShop\Domain\Shared\Money;
use AuraShop\Domain\Shared\NotFound;
use AuraShop\Domain\Shared\Page;
use PHPUnit\Framework\TestCase;

final class CatalogUseCasesTest extends TestCase
{
	public function testFilteringByParentCategorySearchesItsChildrenToo(): void
	{
		$reader = new FakeCatalogReader();
		(new ListProducts($reader))(new CatalogQuery(10));

		$this->assertSame(array(10, 11), $reader->lastCategoryIds);
	}

	public function testWithoutCategoryNoFilterIsSent(): void
	{
		$reader = new FakeCatalogReader();
		(new ListProducts($reader))(new CatalogQuery());

		$this->assertNull($reader->lastCategoryIds);
	}

	public function testUnknownCategoryReturnsEmptyPageWithoutQuerying(): void
	{
		$reader = new FakeCatalogReader();
		$page = (new ListProducts($reader))(new CatalogQuery(999));

		$this->assertSame(0, $page->total);
		$this->assertFalse($reader->searched);
	}

	public function testGetProductThrowsNotFound(): void
	{
		$this->expectException(NotFound::class);
		(new GetProduct(new FakeCatalogReader()))('NOPE');
	}

	public function testGetProductReturnsDetail(): void
	{
		$this->assertSame('DEMO-1', (new GetProduct(new FakeCatalogReader()))(' DEMO-1 ')->ref);
	}
}

final class FakeCatalogReader implements CatalogReader
{
	/** @var list<int>|null */
	public ?array $lastCategoryIds = null;
	public bool $searched = false;

	public function categories(): array
	{
		return array(new Category(10, 'Plata'), new Category(11, 'Cadenas', 10), new Category(20, 'Sudaderas'));
	}

	public function search(CatalogQuery $query, ?array $categoryIds): Page
	{
		$this->searched = true;
		$this->lastCategoryIds = $categoryIds;

		return new Page(array(), 0, $query->page, $query->perPage);
	}

	public function findByRef(string $ref): ?ProductDetail
	{
		if ($ref !== 'DEMO-1') {
			return null;
		}

		return new ProductDetail('DEMO-1', 'Demo', '', '', Money::fromDecimal('10', 'MXN'), array(), array(), Availability::InStock, array(), array());
	}
}
