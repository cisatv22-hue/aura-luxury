<?php
/* Copyright (C) 2026 AURA LUXURY
 *
 * Public REST API front controller. Routes come from PATH_INFO: /custom/aurashop/api/index.php/v1/...
 * (Apache runs with AllowOverride None, so no rewrite rules are needed.)
 */

// JSON endpoint: warnings must never end up in the response body; they still go to the Dolibarr log.
@ini_set('display_errors', '0');

/** @var \AuraShop\Bootstrap\Container $container */
$container = require __DIR__.'/bootstrap.php';

if (!$container->storeEnabled()) {
	\AuraShop\Infrastructure\Http\Response::error(503, 'store_disabled', 'La tienda no está disponible.')->send();
} else {
	$container->kernel()->handle(\AuraShop\Infrastructure\Http\Request::fromGlobals())->send();
}

$db->close();
