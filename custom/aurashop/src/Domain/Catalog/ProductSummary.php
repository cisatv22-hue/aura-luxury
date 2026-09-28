<?php

namespace AuraShop\Domain\Catalog;

use AuraShop\Domain\Shared\Money;

final class ProductSummary
{
	/**
	 * @param list<Category> $categories
	 */
	public function __construct(
		public readonly string $ref,
		public readonly string $label,
		public readonly string $summary,
		public readonly Money $price,
		public readonly ?string $mainImage,
		public readonly array $categories,
		public readonly Availability $availability,
		public readonly bool $hasVariants
	) {
	}
}
