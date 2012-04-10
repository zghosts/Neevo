<?php
/**
 * Neevo - Tiny database layer for PHP. (http://neevo.smasty.net)
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file license.txt.
 *
 * Copyright (c) 2012 Smasty (http://smasty.net)
 *
 */


// PHP compatibility
if(version_compare(PHP_VERSION, '5.3', '<')){
	trigger_error('Neevo requires PHP version 5.3 or newer', E_USER_ERROR);
}


Phar::mapPhar('this.phar');


// Try to turn magic quotes off - Neevo handles SQL quoting.
if(function_exists('set_magic_quotes_runtime'))
	@set_magic_quotes_runtime(false);


// Register autoloader responsible for loading Neevo classes and interfaces.
require_once 'phar://this.phar/Neevo/Loader.php';
Neevo\Loader::getInstance()->register();

__HALT_COMPILER();