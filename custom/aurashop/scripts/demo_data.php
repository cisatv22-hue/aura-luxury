<?php
/* Copyright (C) 2026 AURA LUXURY
 *
 * Creates or removes demo catalog data (refs DEMO-*) using native Dolibarr classes.
 *
 *   docker compose exec -u www-data dolibarr php /var/www/html/custom/aurashop/scripts/demo_data.php seed
 *   docker compose exec -u www-data dolibarr php /var/www/html/custom/aurashop/scripts/demo_data.php purge
 */

if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	exit;
}

define('NOSESSION', 1);
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 1);

require __DIR__.'/../../../master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttribute.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttributeValue.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

const STATE_CONST = 'AURASHOP_DEMO_STATE';
const REF_PREFIX = 'DEMO-';
const VAT_RATE = 16;

$action = $argv[1] ?? '';
if (!in_array($action, array('seed', 'purge'), true)) {
	fwrite(STDERR, "Uso: php demo_data.php seed|purge\n");
	exit(1);
}

$user = new User($db);
if ($user->fetch(0, 'admin') <= 0 && $user->fetch(1) <= 0) {
	fwrite(STDERR, "No se encontró un usuario administrador.\n");
	exit(1);
}
$user->loadRights();

try {
	$action === 'seed' ? seed($db, $user, $conf) : purge($db, $user);
} catch (Throwable $e) {
	fwrite(STDERR, 'ERROR: '.$e->getMessage()."\n");
	exit(1);
}
exit(0);

function seed(DoliDB $db, User $user, Conf $conf): void
{
	if (countDemoProducts($db) > 0) {
		throw new RuntimeException('Ya existen productos DEMO-. Ejecuta "purge" primero.');
	}
	$state = array('categories' => array(), 'attributes' => array(), 'warehouse_created' => false);

	$warehouseId = findWarehouse($db, 'TIENDA');
	if (!$warehouseId) {
		$warehouse = new Entrepot($db);
		$warehouse->ref = 'TIENDA';
		$warehouse->label = 'TIENDA';
		$warehouse->lieu = 'Tienda física';
		$warehouse->statut = 1;
		$warehouse->country_id = 154;
		$warehouseId = check($warehouse->create($user), $warehouse, 'almacén TIENDA');
		$state['warehouse_created'] = true;
		out('Almacén TIENDA creado');
	}

	$catSudaderas = category($db, $user, 'Sudaderas', 0, 'Heavyweight oversized de algodón premium.', $state);
	$catPlata = category($db, $user, 'Plata .925', 0, 'Joyería de Plata Ley .925 con certificado.', $state);
	$catCadenas = category($db, $user, 'Cadenas', $catPlata, 'Cadenas de Plata .925.', $state);
	$catPulseras = category($db, $user, 'Pulseras', $catPlata, 'Pulseras de Plata .925.', $state);
	$catAnillos = category($db, $user, 'Anillos', $catPlata, 'Anillos de Plata .925.', $state);

	[$sizeAttrId, $sizeValues] = sizeAttribute($db, $user, $state);

	$hoodies = array(
		array('DEMO-HOOD-BLK', 'Sudadera Oversized Heavyweight Negra', 1450, array('S' => 5, 'M' => 8, 'L' => 2, 'XL' => 0), array(18, 18, 22), 'Algodón 480 g/m², corte oversized, capucha doble y puño acanalado.'),
		array('DEMO-HOOD-CRM', 'Sudadera Essentials Crema', 1390, array('S' => 3, 'M' => 6, 'L' => 4, 'XL' => 1), array(222, 216, 206), 'Tono crema lavado a la piedra, bordado AURA tono sobre tono.'),
	);
	foreach ($hoodies as [$ref, $label, $price, $stock, $rgb, $description]) {
		$parent = product($db, $user, $ref, $label, $price, $description, array($catSudaderas));
		productImage($conf, $ref, $label, 'SUDADERA', $rgb);
		foreach ($stock as $size => $qty) {
			$combination = new ProductCombination($db);
			$childRef = $ref.'-'.$size;
			$noVariation = array($sizeAttrId => array($sizeValues[$size] => array('price' => 0, 'weight' => 0)));
			$result = $combination->createProductCombination($user, $parent, array($sizeAttrId => $sizeValues[$size]), $noVariation, false, false, false, $childRef);
			if ($result < 0) {
				throw new RuntimeException('No se pudo crear la variante '.$childRef.': '.$combination->error);
			}
			$child = new Product($db);
			$child->fetch(0, $childRef);
			addStock($user, $child, $warehouseId, $qty);
		}
		out($label.' con tallas '.implode(', ', array_keys($stock)));
	}

	$jewelry = array(
		array('DEMO-CAD-CUB', 'Cadena Cubana Plata .925 5 mm', 2890, 4, $catCadenas, 'Eslabón cubano macizo, broche de langosta, 55 cm.'),
		array('DEMO-CAD-ROP', 'Cadena Rope Plata .925 3 mm', 1690, 10, $catCadenas, 'Torzal tipo rope con brillo diamantado, 50 cm.'),
		array('DEMO-PUL-TEN', 'Pulsera Tenis Plata .925 con Zirconias', 1990, 2, $catPulseras, 'Zirconias de 3 mm engastadas a mano, 18 cm.'),
		array('DEMO-ANI-SEL', 'Anillo Sello Plata .925', 1290, 0, $catAnillos, 'Sello liso para grabado personalizado.'),
	);
	foreach ($jewelry as [$ref, $label, $price, $qty, $categoryId, $description]) {
		$product = product($db, $user, $ref, $label, $price, $description, array($categoryId));
		productImage($conf, $ref, $label, 'PLATA .925', array(196, 202, 212));
		addStock($user, $product, $warehouseId, $qty);
		out($label.' (stock '.$qty.')');
	}

	dolibarr_set_const($db, STATE_CONST, json_encode($state), 'chaine', 0, 'Demo data created by AuraShop', $conf->entity);
	out('Listo: '.countDemoProducts($db).' productos DEMO- creados.');
}

function purge(DoliDB $db, User $user): void
{
	global $conf;

	$refs = array();
	$resql = $db->query("SELECT rowid, ref FROM ".MAIN_DB_PREFIX."product WHERE ref LIKE '".REF_PREFIX."%'");
	while ($resql && ($obj = $db->fetch_object($resql))) {
		$refs[(int) $obj->rowid] = $obj->ref;
	}

	foreach (array_keys($refs) as $productId) {
		$combination = new ProductCombination($db);
		foreach ($combination->fetchAllByFkProductParent($productId) ?: array() as $comb) {
			$comb->delete($user);
		}
	}
	if ($refs) {
		$ids = implode(',', array_keys($refs));
		$db->query("DELETE FROM ".MAIN_DB_PREFIX."stock_mouvement WHERE fk_product IN (".$ids.")");
		$db->query("DELETE FROM ".MAIN_DB_PREFIX."product_stock WHERE fk_product IN (".$ids.")");
	}
	// Variants first, then parents.
	krsort($refs);
	foreach ($refs as $productId => $ref) {
		$product = new Product($db);
		$product->fetch($productId);
		if ($product->delete($user) <= 0) {
			throw new RuntimeException('No se pudo borrar '.$ref.': '.$product->error);
		}
	}
	out(count($refs).' productos DEMO- eliminados');

	$state = json_decode(getDolGlobalString(STATE_CONST, '{}'), true) ?: array();
	foreach (array_reverse($state['categories'] ?? array()) as $categoryId) {
		$category = new Categorie($db);
		if ($category->fetch($categoryId) > 0) {
			$category->delete($user);
		}
	}
	foreach ($state['attributes'] ?? array() as $attributeId) {
		$db->query("DELETE FROM ".MAIN_DB_PREFIX."product_attribute_value WHERE fk_product_attribute = ".((int) $attributeId));
		$attribute = new ProductAttribute($db);
		if ($attribute->fetch($attributeId) > 0) {
			$attribute->delete($user);
		}
	}
	out(count($state['categories'] ?? array()).' categorías y '.count($state['attributes'] ?? array()).' atributo(s) de demo eliminados');
	if (!empty($state['warehouse_created'])) {
		out('El almacén TIENDA se conserva: lo necesitarás para el inventario real.');
	}
	dolibarr_del_const($db, STATE_CONST, $conf->entity);
}

function category(DoliDB $db, User $user, string $label, int $parentId, string $description, array &$state): int
{
	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."categorie WHERE type = 0 AND label = '".$db->escape($label)."'";
	$sql .= " AND fk_parent = ".$parentId." AND entity IN (".getEntity('category').")";
	$resql = $db->query($sql);
	if ($resql && ($obj = $db->fetch_object($resql))) {
		return (int) $obj->rowid;
	}
	$category = new Categorie($db);
	$category->label = $label;
	$category->description = $description;
	$category->type = Categorie::TYPE_PRODUCT;
	$category->fk_parent = $parentId;
	$category->visible = 1;
	$id = check($category->create($user), $category, 'categoría '.$label);
	$state['categories'][] = $id;

	return $id;
}

/**
 * @return array{0: int, 1: array<string, int>}
 */
function sizeAttribute(DoliDB $db, User $user, array &$state): array
{
	$attribute = new ProductAttribute($db);
	$resql = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."product_attribute WHERE ref = 'TALLA' AND entity IN (".getEntity('product').")");
	if ($resql && ($obj = $db->fetch_object($resql))) {
		$attribute->fetch((int) $obj->rowid);
	} else {
		$attribute->ref = 'TALLA';
		$attribute->label = 'Talla';
		check($attribute->create($user), $attribute, 'atributo Talla');
		$state['attributes'][] = (int) $attribute->id;
	}

	$values = array();
	$resql = $db->query("SELECT rowid, ref FROM ".MAIN_DB_PREFIX."product_attribute_value WHERE fk_product_attribute = ".((int) $attribute->id));
	while ($resql && ($obj = $db->fetch_object($resql))) {
		$values[$obj->ref] = (int) $obj->rowid;
	}
	foreach (array('S', 'M', 'L', 'XL') as $position => $size) {
		if (isset($values[$size])) {
			continue;
		}
		$value = new ProductAttributeValue($db);
		$value->fk_product_attribute = $attribute->id;
		$value->ref = $size;
		$value->value = $size;
		$value->position = $position;
		$values[$size] = check($value->create($user), $value, 'talla '.$size);
	}

	return array((int) $attribute->id, $values);
}

/**
 * @param list<int> $categoryIds
 */
function product(DoliDB $db, User $user, string $ref, string $label, float $priceTtc, string $description, array $categoryIds): Product
{
	$product = new Product($db);
	$product->ref = $ref;
	$product->label = $label;
	$product->description = $description;
	$product->type = Product::TYPE_PRODUCT;
	$product->status = 1;
	$product->status_buy = 1;
	$product->price_base_type = 'TTC';
	$product->price_ttc = $priceTtc;
	$product->price = $priceTtc;
	$product->tva_tx = VAT_RATE;
	check($product->create($user), $product, 'producto '.$ref);
	$product->updatePrice($priceTtc, 'TTC', $user, VAT_RATE);

	foreach ($categoryIds as $categoryId) {
		$category = new Categorie($db);
		$category->fetch($categoryId);
		$category->add_type($product, Categorie::TYPE_PRODUCT);
	}
	$product->fetch($product->id);

	return $product;
}

function addStock(User $user, Product $product, int $warehouseId, int $qty): void
{
	if ($qty > 0 && $product->correct_stock($user, $warehouseId, $qty, 0, 'Stock inicial DEMO') <= 0) {
		throw new RuntimeException('No se pudo cargar stock de '.$product->ref.': '.$product->error);
	}
}

/**
 * Generates a simple branded placeholder photo plus Dolibarr's own thumbs, as if it had been uploaded.
 *
 * @param array{0: int, 1: int, 2: int} $rgb
 */
function productImage(Conf $conf, string $ref, string $label, string $kicker, array $rgb): void
{
	$dir = $conf->product->multidir_output[$conf->entity].'/'.dol_sanitizeFileName($ref).'/';
	dol_mkdir($dir);
	$w = 1200;
	$h = 1500;
	$img = imagecreatetruecolor($w, $h);
	$dark = $rgb[0] + $rgb[1] + $rgb[2] < 300;
	for ($y = 0; $y < $h; $y++) {
		$t = $y / $h;
		$shade = $dark ? 1 - 0.35 * $t : 1 - 0.18 * $t;
		imageline($img, 0, $y, $w, $y, imagecolorallocate($img, (int) ($rgb[0] * $shade), (int) ($rgb[1] * $shade), (int) ($rgb[2] * $shade)));
	}
	$ink = $dark ? imagecolorallocate($img, 226, 232, 240) : imagecolorallocate($img, 22, 22, 28);
	$soft = $dark ? imagecolorallocatealpha($img, 226, 232, 240, 100) : imagecolorallocatealpha($img, 22, 22, 28, 105);
	imagesetthickness($img, 6);
	imageellipse($img, (int) ($w / 2), (int) ($h * 0.42), 620, 620, $soft);
	imageellipse($img, (int) ($w / 2), (int) ($h * 0.42), 520, 520, $soft);

	$fontBold = DOL_DOCUMENT_ROOT.'/includes/fonts/Roboto-Medium.ttf';
	$fontThin = DOL_DOCUMENT_ROOT.'/includes/fonts/Roboto-Regular.ttf';
	centeredText($img, 'A U R A', $fontBold, 96, (int) ($h * 0.445), $ink, $w);
	centeredText($img, $kicker, $fontThin, 34, (int) ($h * 0.50), $ink, $w);
	$lines = explode("\n", wordwrap($label, 26));
	foreach ($lines as $i => $line) {
		centeredText($img, $line, $fontThin, 44, (int) ($h * 0.80) + $i * 64, $ink, $w);
	}

	$file = $dir.strtolower($ref).'.jpg';
	imagejpeg($img, $file, 88);
	imagedestroy($img);

	$sizes = getDefaultImageSizes();
	vignette($file, $sizes['maxwidthsmall'], $sizes['maxheightsmall'], '_small', $sizes['quality'], 'thumbs');
	vignette($file, $sizes['maxwidthmini'], $sizes['maxheightmini'], '_mini', $sizes['quality'], 'thumbs');
}

function centeredText(GdImage $img, string $text, string $font, int $size, int $baseline, int $color, int $width): void
{
	$box = imagettfbbox($size, 0, $font, $text);
	$x = (int) (($width - ($box[2] - $box[0])) / 2);
	imagettftext($img, $size, 0, $x, $baseline, $color, $font, $text);
}

function findWarehouse(DoliDB $db, string $ref): int
{
	$resql = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."entrepot WHERE ref = '".$db->escape($ref)."' AND entity IN (".getEntity('stock').")");

	return ($resql && ($obj = $db->fetch_object($resql))) ? (int) $obj->rowid : 0;
}

function countDemoProducts(DoliDB $db): int
{
	$resql = $db->query("SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."product WHERE ref LIKE '".REF_PREFIX."%'");

	return ($resql && ($obj = $db->fetch_object($resql))) ? (int) $obj->nb : 0;
}

function check(int $result, CommonObject $object, string $what): int
{
	if ($result <= 0) {
		throw new RuntimeException('No se pudo crear '.$what.': '.($object->error ?: implode(', ', (array) $object->errors)));
	}

	return $result;
}

function out(string $message): void
{
	echo '  · '.$message."\n";
}
