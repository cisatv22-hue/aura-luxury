<?php
/* Copyright (C) 2026 AURA LUXURY
 * Portal publico de la tienda (no requiere login de Dolibarr)
 */

if (!defined('NOLOGIN')) define('NOLOGIN', '1');
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1');
if (!defined('NOIPCHECK')) define('NOIPCHECK', '1');
if (!defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');

require '../../main.inc.php';

if (!isModEnabled('aurashop')) {
	httponly_accessforbidden('Module AuraShop not enabled');
}

$langs->loadLangs(array('main', 'aurashop@aurashop'));

$storeName = getDolGlobalString('MAIN_INFO_SOCIETE_NOM', 'AURA LUXURY');
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo dol_escape_htmltag($storeName); ?></title>
</head>
<body>
	<h1><?php echo dol_escape_htmltag($storeName); ?></h1>
	<p><?php echo $langs->trans('AuraShopPortal'); ?></p>
</body>
</html>
