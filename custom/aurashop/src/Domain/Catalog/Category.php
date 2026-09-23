<?php

namespace AuraShop\Domain\Catalog;

final class Category
{
	public function __construct(
		public readonly int $id,
		public readonly string $label,
		public readonly ?int $parentId = null,
		public readonly string $description = ''
	) {
	}
}
