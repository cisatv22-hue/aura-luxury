<?php

namespace AuraShop\Infrastructure\Dolibarr;

use AuraShop\Application\Port\StoreSettings;

final class DolibarrStoreSettings implements StoreSettings
{
	private const DEFAULT_NAME = 'AURA LUXURY';

	public function __construct(private readonly string $currency)
	{
	}

	public function storeName(): string
	{
		return getDolGlobalString('MAIN_INFO_SOCIETE_NOM', self::DEFAULT_NAME);
	}

	public function currency(): string
	{
		return $this->currency;
	}

	public function whatsappNumber(): ?string
	{
		$digits = preg_replace('/\D+/', '', getDolGlobalString('AURASHOP_WHATSAPP'));

		return $digits === '' ? null : $digits;
	}
}
