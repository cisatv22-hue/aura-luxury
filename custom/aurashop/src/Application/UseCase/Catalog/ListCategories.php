<?php

namespace AuraShop\Application\UseCase\Catalog;

use AuraShop\Application\Port\CatalogReader;
use AuraShop\Domain\Catalog\Category;

final class ListCategories
{
	public function __construct(private readonly CatalogReader $catalog)
	{
	}

	/**
	 * @return list<Category>
	 */
	public function __invoke(): array
	{
		return $this->catalog->categories();
	}
}
