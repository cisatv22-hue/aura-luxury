<?php
/* Copyright (C) 2026 AURA LUXURY
 *
 * Loads Dolibarr once (no login), registers the AuraShop autoloader and builds the container.
 * Shared by the API (api/index.php) and the storefront shell (public/AuraShop/index.php); each entry point
 * checks Container::storeEnabled() and answers in its own format (JSON or HTML).
 */

if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1');
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

$res = 0;
if (!$res && file_exists(__DIR__.'/../../../main.inc.php')) {
	$res = @include __DIR__.'/../../../main.inc.php';
}
if (!$res && file_exists(__DIR__.'/../../../../main.inc.php')) {
	$res = @include __DIR__.'/../../../../main.inc.php';
}
if (!$res) {
	http_response_code(500);
	die('Include of main fails');
}

require_once __DIR__.'/../src/Support/Autoloader.php';
\AuraShop\Support\Autoloader::register();

return new \AuraShop\Bootstrap\Container($db, $conf);
