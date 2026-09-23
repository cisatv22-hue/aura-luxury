<?php

namespace AuraShop\Application\Port;

use AuraShop\Domain\Catalog\CatalogQuery;
use AuraShop\Domain\Catalog\Category;
use AuraShop\Domain\Catalog\ProductDetail;
use AuraShop\Domain\Catalog\ProductSummary;
use AuraShop\Domain\Shared\Page;

interface CatalogReader
{
	/**
	 * @return list<Category>
	 */
	public function categories(): array;

	/**
	 * @param list<int>|null $categoryIds null = no category filter
	 * @return Page<ProductSummary>
	 */
	public function search(CatalogQuery $query, ?array $categoryIds): Page;

	public function findByRef(string $ref): ?ProductDetail;
}
