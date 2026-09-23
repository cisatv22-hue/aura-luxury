<?php

namespace AuraShop\Support;

/**
 * PSR-4 autoloader for the AuraShop\ namespace, so the module runs without Composer at runtime.
 */
final class Autoloader
{
	private const PREFIX = 'AuraShop\\';

	public static function register(): void
	{
		spl_autoload_register(static function (string $class): void {
			if (strncmp($class, self::PREFIX, strlen(self::PREFIX)) !== 0) {
				return;
			}
			$relative = substr($class, strlen(self::PREFIX));
			$file = dirname(__DIR__).'/'.str_replace('\\', '/', $relative).'.php';
			if (is_file($file)) {
				require_once $file;
			}
		});
	}
}
