<?php

namespace Setsuna\Cache;
use \Memcache;

require 'CacheInterface.php';


class MemcacheCache implements CacheInterface
{
	//Memcache对象
	static public $_memcache = NULL;
	static public $_prefix = NULL;


	public static function getInstance() {

		if (!extension_loaded('memcache')) {
			return '';
		}

		if(empty(self::$_memcache)){
			$_memcache = new Memcache();
			$_memcache->addServer('localhost' , 11211);
			self::$_memcache = $_memcache;
		}
		return self::$_memcache;
	}


	static public function getCache() {
		return empty(self::$_memcache) ? self::getInstance() : self::$_memcache;
	}


	static public function set( $key , $value , $time = 0) {
		return self::getCache()->set(self::$_prefix . md5($key) , $value );
	}

	static public function get( $key ) {
		return self::getCache()->get(self::$_prefix . md5($key));
	}


	static public function remove($key) {
		return self::getCache()->delete(self::$_prefix . md5($key), 0);
	}

	static public function getMultiple(array $keys) { 

	}

	function __destruct() {
		self::$_memcache->close();
	}


}
