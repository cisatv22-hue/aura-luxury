<?php

namespace AuraShop\Application\Port;

enum ImageSize: string
{
	case Mini = 'mini';
	case Small = 'small';
	case Card = 'card';
	case Full = 'full';

	public static function fromInput(?string $value): self
	{
		return self::tryFrom((string) $value) ?? self::Full;
	}
}
