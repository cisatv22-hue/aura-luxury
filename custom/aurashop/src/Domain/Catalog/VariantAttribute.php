<?php

namespace AuraShop\Domain\Catalog;

/**
 * A selectable dimension of a product, e.g. Talla with values S, M, L.
 */
final class VariantAttribute
{
	/**
	 * @param list<string> $values ordered as configured in Dolibarr
	 */
	public function __construct(
		public readonly string $code,
		public readonly string $label,
		public readonly array $values
	) {
	}
}
