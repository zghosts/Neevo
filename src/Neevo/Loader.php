<?php
/**
 * Neevo - Tiny database layer for PHP. (http://neevo.smasty.net)
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file license.txt.
 *
 * Copyright (c) 2011 Martin Srank (http://smasty.net)
 *
 */

namespace Neevo;


/**
 * Autoloader responsible for loading Neevo classes and interfaces.
 * @author Martin Srank
 */
class Loader {


	/** @var array */
	private $list = array(
		'neevo\\basestatement' => '/BaseStatement.php',
		'neevo\\cache' => '/Cache.php',
		'neevo\\cache\\filestorage' => '/Cache/FileStorage.php',
		'neevo\\cache\\memcachestorage' => '/Cache/MemcacheStorage.php',
		'neevo\\cache\\memorystorage' => '/Cache/MemoryStorage.php',
		'neevo\\cache\\sessionstorage' => '/Cache/SessionStorage.php',
		'neevo\\connection' => '/Connection.php',
		'neevo\\driver' => '/Driver.php',
		'neevo\\drivers\\driverexception' => '/DriverException.php',
		'neevo\\drivers\\implementationexception' => '/DriverException.php',
		'neevo\\literal' => '/Manager.php',
		'neevo\\manager' => '/Manager.php',
		'neevo\\neevoexception' => '/Exception.php',
		'neevo\\observer\\objectmap' => '/Observer/ObjectMap.php',
		'neevo\\observer\\observer' => '/Observer/Observer.php',
		'neevo\\observer\\subject' => '/Observer/Subject.php',
		'neevo\\parser' => '/Parser.php',
		'neevo\\result' => '/Result.php',
		'neevo\\resultiterator' => '/ResultIterator.php',
		'neevo\\row' => '/Row.php',
		'neevo\\statement' => '/Statement.php',
		'neevocachenette' => '/Nette/NeevoCacheNette.php',
		'neevofactory' => '/Nette/NeevoFactory.php',
		'neevopanel' => '/Nette/NeevoPanel.php',
	);

	/** @var Loader */
	private static $instance;


	private function __construct(){}


	/**
	* Get the singleton instance.
	* @return Loader
	*/
	public static function getInstance(){
		if(self::$instance === null)
			self::$instance = new self;
		return self::$instance;
	}


	/**
	 * Register the autoloader.
	 * @return void
	 */
	public function register(){
		spl_autoload_register(array($this, 'tryLoad'));
	}


	/**
	 * Unregister the autoloader.
	 * @return void
	 */
	public function unregister(){
		spl_autoload_unregister(array($this, 'tryLoad'));
	}


	/**
	 * Try load Neevo class/interface.
	 * @param string $type
	 * @return bool
	 */
	public function tryLoad($type){
		$type = trim(strtolower($type), '\\');

		if(isset($this->list[$type]))
			return include_once dirname(__FILE__) . $this->list[$type];
		return false;
	}


}
