<?php

require __DIR__.'/../vendor/autoload.php';

// Integration tests need Dolibarr (and its database). Only inside the container: AURASHOP_DOLIBARR=1.
if (getenv('AURASHOP_DOLIBARR') === '1') {
	// PHPUnit includes this file from inside a method, so Dolibarr's globals must be declared explicitly.
	global $conf, $db, $langs, $user, $mysoc, $hookmanager, $dolibarr_main_data_root;
	define('NOSESSION', 1);
	require __DIR__.'/../../../master.inc.php';
}
