<?php
/* Copyright (C) 2026 AURA LUXURY
 *
 * Storefront shell. Serves the Vue SPA built into ./assets and renders SEO meta for the current route
 * on the server, so product pages are indexable even though the UI is client-side.
 *
 * Routes use PATH_INFO (index.php/producto/REF) because Apache runs with AllowOverride None.
 */

/** @var \AuraShop\Bootstrap\Container $container */
$container = require __DIR__.'/../../custom/aurashop/api/bootstrap.php';

use AuraShop\Domain\Shared\NotFound;

if (!$container->storeEnabled()) {
	http_response_code(503);
	header('Content-Type: text/html; charset=utf-8');
	header('Retry-After: 3600');
	echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
		.'<meta name="robots" content="noindex"><title>Volvemos pronto</title></head>'
		.'<body style="margin:0;min-height:100vh;display:grid;place-items:center;background:#08080a;color:#e2e8f0;font-family:system-ui,sans-serif;text-align:center">'
		.'<div><p style="letter-spacing:.4em;font-size:28px;margin:0 0 12px">A U R A</p><p style="color:#a1a1aa;margin:0">La tienda está en mantenimiento. Volvemos pronto.</p></div>'
		.'</body></html>';
	exit;
}

$scriptName = (string) $_SERVER['SCRIPT_NAME'];
$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);

// /public/AuraShop/ -> /public/AuraShop/index.php so the SPA router base always matches the URL.
if (strpos($requestPath, $scriptName) !== 0) {
	$query = (string) ($_SERVER['QUERY_STRING'] ?? '');
	header('Location: '.$scriptName.($query !== '' ? '?'.$query : ''), true, 302);
	exit;
}

$path = '/'.trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');
$origin = (string) preg_replace('#^(https?://[^/]+).*$#', '$1', DOL_MAIN_URL_ROOT);
$store = $container->storeSettings();
$storeName = $store->storeName();

$meta = array(
	'title' => $storeName.' | Essentials Streetwear & Plata Ley .925',
	'description' => 'Sudaderas oversized heavyweight y joyería de Plata Ley .925. Envíos a todo México.',
	'image' => null,
	'type' => 'website',
	'jsonld' => null,
);
$status = 200;

if (preg_match('#^/producto/([^/]+)$#', $path, $m)) {
	try {
		$product = $container->getProduct()($m[1]);
		$data = $container->catalogPresenter()->detail($product);
		$priceFrom = $product->priceFrom();
		$meta['title'] = $product->label.' | '.$storeName;
		$meta['description'] = $product->summary !== '' ? $product->summary : $product->label;
		$meta['type'] = 'product';
		$meta['image'] = isset($data['images'][0]) ? $origin.$data['images'][0]['full'] : null;
		$availability = array('in_stock' => 'InStock', 'low_stock' => 'LimitedAvailability', 'out_of_stock' => 'OutOfStock');
		$meta['jsonld'] = array(
			'@context' => 'https://schema.org',
			'@type' => 'Product',
			'name' => $product->label,
			'sku' => $product->ref,
			'description' => $product->summary,
			'image' => array_map(static fn ($img) => $origin.$img['full'], $data['images']),
			'brand' => array('@type' => 'Brand', 'name' => $storeName),
			'offers' => array(
				'@type' => 'Offer',
				'price' => $priceFrom->toDecimalString(),
				'priceCurrency' => $priceFrom->currency,
				'availability' => 'https://schema.org/'.$availability[$product->availability->value],
				'url' => $origin.$scriptName.$path,
			),
		);
	} catch (NotFound $e) {
		$status = 404;
		$meta['title'] = 'Producto no encontrado | '.$storeName;
	}
} elseif ($path === '/catalogo') {
	$meta['title'] = 'Catálogo | '.$storeName;
} elseif ($path !== '/') {
	$status = 404;
	$meta['title'] = 'Página no encontrada | '.$storeName;
}

$manifestFile = __DIR__.'/assets/.vite/manifest.json';
$entry = is_readable($manifestFile) ? (json_decode((string) file_get_contents($manifestFile), true)['src/main.ts'] ?? null) : null;
if ($entry === null) {
	http_response_code(503);
	header('Content-Type: text/plain; charset=utf-8');
	echo "La tienda aún no está compilada. Ejecuta el build del frontend (ver custom/aurashop/README.md).\n";
	exit;
}
$assetBase = rtrim(dirname($scriptName), '/').'/assets/';

$runtime = array(
	'apiBase' => $container->apiBaseUrl(),
	'routerBase' => $scriptName,
	'store' => array(
		'storeName' => $storeName,
		'currency' => $store->currency(),
		'whatsapp' => $store->whatsappNumber(),
	),
);
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;

http_response_code($status);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$e = static fn (?string $v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $e($meta['title']); ?></title>
	<meta name="description" content="<?php echo $e($meta['description']); ?>">
	<link rel="canonical" href="<?php echo $e($origin.$scriptName.($path === '/' ? '' : $path)); ?>">
	<meta property="og:site_name" content="<?php echo $e($storeName); ?>">
	<meta property="og:type" content="<?php echo $e($meta['type']); ?>">
	<meta property="og:title" content="<?php echo $e($meta['title']); ?>">
	<meta property="og:description" content="<?php echo $e($meta['description']); ?>">
<?php if ($meta['image']) { ?>
	<meta property="og:image" content="<?php echo $e($meta['image']); ?>">
<?php } ?>
<?php if ($status !== 200) { ?>
	<meta name="robots" content="noindex">
<?php } ?>
	<meta name="theme-color" content="#08080a">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?php foreach ($entry['css'] ?? array() as $css) { ?>
	<link rel="stylesheet" href="<?php echo $e($assetBase.$css); ?>">
<?php } ?>
	<script>window.__AURASHOP__ = <?php echo json_encode($runtime, $jsonFlags); ?>;</script>
<?php if ($meta['jsonld']) { ?>
	<script type="application/ld+json"><?php echo json_encode($meta['jsonld'], $jsonFlags); ?></script>
<?php } ?>
	<script type="module" src="<?php echo $e($assetBase.$entry['file']); ?>"></script>
</head>
<body class="bg-brand-bg">
	<div id="app"></div>
	<noscript><p style="color:#e2e8f0;padding:2rem;font-family:sans-serif">Activa JavaScript para ver la tienda.</p></noscript>
</body>
</html>
<?php
$db->close();
