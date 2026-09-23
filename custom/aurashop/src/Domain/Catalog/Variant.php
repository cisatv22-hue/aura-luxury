<?php

namespace AuraShop\Domain\Catalog;

use AuraShop\Domain\Shared\Money;

final class Variant
{
	/**
	 * @param array<string, string> $options attribute code => value
	 */
	public function __construct(
		public readonly string $ref,
		public readonly array $options,
		public readonly Money $price,
		public readonly Availability $availability
	) {
	}
}
