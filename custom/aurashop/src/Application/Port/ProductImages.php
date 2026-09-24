<?php

namespace AuraShop\Application\Port;

interface ProductImages
{
	/**
	 * @return list<string> file names, main image first
	 */
	public function list(string $productRef): array;

	/**
	 * Absolute path of a readable image, or null if it does not exist or is not a public product image.
	 */
	public function resolve(string $productRef, string $fileName, ImageSize $size): ?string;
}
