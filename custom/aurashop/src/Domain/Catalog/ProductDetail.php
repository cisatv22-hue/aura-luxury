<?php

namespace AuraShop\Domain\Catalog;

use AuraShop\Domain\Shared\Money;

final class ProductDetail
{
	/**
	 * @param list<string>           $images     file names, first one is the main image
	 * @param list<Category>         $categories
	 * @param list<VariantAttribute> $attributes
	 * @param list<Variant>          $variants
	 */
	public function __construct(
		public readonly string $ref,
		public readonly string $label,
		public readonly string $summary,
		public readonly string $descriptionHtml,
		public readonly Money $price,
		public readonly array $images,
		public readonly array $categories,
		public readonly Availability $availability,
		public readonly array $attributes,
		public readonly array $variants
	) {
	}

	public function hasVariants(): bool
	{
		return $this->variants !== array();
	}

	/**
	 * Lowest variant price, used for "desde $X" when variants have different prices.
	 */
	public function priceFrom(): Money
	{
		$lowest = $this->price;
		foreach ($this->variants as $variant) {
			if ($variant->price->amount < $lowest->amount) {
				$lowest = $variant->price;
			}
		}

		return $lowest;
	}
}
