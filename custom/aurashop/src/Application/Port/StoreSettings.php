<?php

namespace AuraShop\Application\Port;

interface StoreSettings
{
	public function storeName(): string;

	public function currency(): string;

	/** Digits only with country code, or null when not configured. */
	public function whatsappNumber(): ?string;
}
