<?php

namespace AuraShop\Application\UseCase\Catalog;

use AuraShop\Application\Port\CatalogReader;
use AuraShop\Domain\Catalog\ProductDetail;
use AuraShop\Domain\Shared\NotFound;

final class GetProduct
{
	public function __construct(private readonly CatalogReader $catalog)
	{
	}

	public function __invoke(string $ref): ProductDetail
	{
		$ref = trim($ref);
		$product = $ref === '' ? null : $this->catalog->findByRef($ref);
		if ($product === null) {
			throw new NotFound('Product not found: '.$ref);
		}

		return $product;
	}
}
