<?php

namespace AuraShop\Domain\Catalog;

/**
 * What the customer sees. Exact stock quantities are never exposed.
 */
enum Availability: string
{
	case InStock = 'in_stock';
	case LowStock = 'low_stock';
	case OutOfStock = 'out_of_stock';

	public const LOW_STOCK_THRESHOLD = 3;

	public static function fromQuantity(float $quantity): self
	{
		if ($quantity <= 0) {
			return self::OutOfStock;
		}

		return $quantity <= self::LOW_STOCK_THRESHOLD ? self::LowStock : self::InStock;
	}

	public function isPurchasable(): bool
	{
		return $this !== self::OutOfStock;
	}
}
