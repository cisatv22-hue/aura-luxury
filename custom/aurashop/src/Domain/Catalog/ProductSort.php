<?php

namespace AuraShop\Domain\Catalog;

enum ProductSort: string
{
	case Newest = 'newest';
	case PriceAsc = 'price_asc';
	case PriceDesc = 'price_desc';
	case Name = 'name';

	public static function fromInput(?string $value): self
	{
		return self::tryFrom((string) $value) ?? self::Newest;
	}
}
