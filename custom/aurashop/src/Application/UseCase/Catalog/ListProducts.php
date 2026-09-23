<?php

namespace AuraShop\Application\UseCase\Catalog;

use AuraShop\Application\Port\CatalogReader;
use AuraShop\Domain\Catalog\CatalogQuery;
use AuraShop\Domain\Catalog\CategoryTree;
use AuraShop\Domain\Catalog\ProductSummary;
use AuraShop\Domain\Shared\Page;

final class ListProducts
{
	public function __construct(private readonly CatalogReader $catalog)
	{
	}

	/**
	 * @return Page<ProductSummary>
	 */
	public function __invoke(CatalogQuery $query): Page
	{
		$categoryIds = null;
		if ($query->categoryId !== null) {
			$tree = new CategoryTree($this->catalog->categories());
			$categoryIds = $tree->withDescendants($query->categoryId);
			if ($categoryIds === array()) {
				return new Page(array(), 0, $query->page, $query->perPage);
			}
		}

		return $this->catalog->search($query, $categoryIds);
	}
}
