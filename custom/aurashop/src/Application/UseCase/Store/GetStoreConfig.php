<?php

namespace AuraShop\Application\UseCase\Store;

use AuraShop\Application\Port\StoreSettings;

final class GetStoreConfig
{
	public function __construct(private readonly StoreSettings $settings)
	{
	}

	/**
	 * @return array{storeName: string, currency: string, whatsapp: ?string}
	 */
	public function __invoke(): array
	{
		return array(
			'storeName' => $this->settings->storeName(),
			'currency' => $this->settings->currency(),
			'whatsapp' => $this->settings->whatsappNumber(),
		);
	}
}
